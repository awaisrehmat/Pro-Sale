<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('closing_summary')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'start_date', 'end_date']);
        });

        Schema::create('financial_year_party_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained()->cascadeOnDelete();
            $table->string('party_type', 20);
            $table->unsignedBigInteger('party_id');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['financial_year_id', 'party_type', 'party_id'], 'fy_party_balance_unique');
        });

        Schema::create('financial_year_stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('opening_quantity', 15, 3)->default(0);
            $table->decimal('opening_unit_cost', 15, 2)->default(0);
            $table->decimal('opening_value', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['financial_year_id', 'product_id'], 'fy_stock_balance_unique');
        });

        Schema::create('financial_year_account_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained()->cascadeOnDelete();
            $table->string('account_key', 30);
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['financial_year_id', 'account_key'], 'fy_account_balance_unique');
        });

        foreach (['purchases', 'sales', 'payments', 'expenses', 'stock_movements', 'stock_adjustments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('financial_year_id')->nullable()->after('company_id')->constrained()->restrictOnDelete();
            });
        }

        $dateColumns = ['purchases'=>'purchase_date','sales'=>'sale_date','payments'=>'payment_date','expenses'=>'expense_date','stock_movements'=>'movement_date','stock_adjustments'=>'adjustment_date'];
        $currentStartYear = now()->month >= 7 ? now()->year : now()->year - 1;
        foreach (DB::table('companies')->orderBy('id')->get() as $company) {
            $minimumDate = collect($dateColumns)->map(fn ($dateColumn, $tableName) => DB::table($tableName)->where('company_id', $company->id)->min($dateColumn))->filter()->min();
            $minimum = $minimumDate ? Carbon::parse($minimumDate) : now();
            $firstStartYear = $minimum->month >= 7 ? $minimum->year : $minimum->year - 1;
            for ($startYear = $firstStartYear; $startYear <= $currentStartYear; $startYear++) {
                $start = Carbon::create($startYear, 7, 1)->startOfDay();
                $end = $start->copy()->addYear()->subDay();
                $id = DB::table('financial_years')->insertGetId([
                    'company_id'=>$company->id,'name'=>$startYear.'-'.($startYear+1),'start_date'=>$start->toDateString(),'end_date'=>$end->toDateString(),
                    'status'=>$startYear===$currentStartYear?'active':'closed','closed_at'=>$startYear===$currentStartYear?null:now(),'created_at'=>now(),'updated_at'=>now(),
                ]);
                foreach ($dateColumns as $tableName => $dateColumn) DB::table($tableName)->where('company_id',$company->id)->whereBetween($dateColumn,[$start->toDateString(),$end->toDateString()])->update(['financial_year_id'=>$id]);

                foreach (DB::table('suppliers')->where('company_id',$company->id)->get() as $supplier) {
                    $purchases=(float)DB::table('purchases')->where('company_id',$company->id)->where('supplier_id',$supplier->id)->where('status','completed')->whereDate('purchase_date','<',$start)->sum('grand_total');
                    $paid=(float)DB::table('payments')->where('company_id',$company->id)->where('supplier_id',$supplier->id)->where('payment_type','supplier_payment')->where('is_reversed',false)->whereDate('payment_date','<',$start)->sum('amount');
                    $openingBalance=(float)$supplier->opening_balance+$purchases-$paid;
                    DB::table('financial_year_party_balances')->insert(['company_id'=>$company->id,'financial_year_id'=>$id,'party_type'=>'supplier','party_id'=>$supplier->id,'opening_balance'=>$openingBalance,'created_at'=>now(),'updated_at'=>now()]);
                    if($startYear===$currentStartYear)DB::table('suppliers')->where('id',$supplier->id)->update(['opening_balance'=>$openingBalance]);
                }
                foreach (DB::table('customers')->where('company_id',$company->id)->get() as $customer) {
                    $sales=(float)DB::table('sales')->where('company_id',$company->id)->where('customer_id',$customer->id)->where('status','completed')->whereDate('sale_date','<',$start)->sum('grand_total');
                    $received=(float)DB::table('payments')->where('company_id',$company->id)->where('customer_id',$customer->id)->where('payment_type','customer_payment')->where('is_reversed',false)->whereDate('payment_date','<',$start)->sum('amount');
                    $openingBalance=(float)$customer->opening_balance+$sales-$received;
                    DB::table('financial_year_party_balances')->insert(['company_id'=>$company->id,'financial_year_id'=>$id,'party_type'=>'customer','party_id'=>$customer->id,'opening_balance'=>$openingBalance,'created_at'=>now(),'updated_at'=>now()]);
                    if($startYear===$currentStartYear)DB::table('customers')->where('id',$customer->id)->update(['opening_balance'=>$openingBalance]);
                }
                foreach (DB::table('products')->where('company_id',$company->id)->get() as $product) {
                    $last=DB::table('stock_movements')->where('company_id',$company->id)->where('product_id',$product->id)->whereDate('movement_date','<',$start)->orderByDesc('movement_date')->orderByDesc('id')->first();
                    $quantity=(float)($last->stock_after??0);$cost=(float)($last->unit_cost??$product->average_cost??0);
                    DB::table('financial_year_stock_balances')->insert(['company_id'=>$company->id,'financial_year_id'=>$id,'product_id'=>$product->id,'opening_quantity'=>$quantity,'opening_unit_cost'=>$cost,'opening_value'=>round($quantity*$cost,2),'created_at'=>now(),'updated_at'=>now()]);
                }
                foreach (['cash','bank_transfer','card','other'] as $method) {
                    $received=(float)DB::table('payments')->where('company_id',$company->id)->where('payment_method',$method)->where('payment_type','customer_payment')->where('is_reversed',false)->whereDate('payment_date','<',$start)->sum('amount');
                    $paid=(float)DB::table('payments')->where('company_id',$company->id)->where('payment_method',$method)->where('payment_type','!=','customer_payment')->where('is_reversed',false)->whereDate('payment_date','<',$start)->sum('amount');
                    DB::table('financial_year_account_balances')->insert(['company_id'=>$company->id,'financial_year_id'=>$id,'account_key'=>$method,'opening_balance'=>$received-$paid,'created_at'=>now(),'updated_at'=>now()]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (['stock_adjustments', 'stock_movements', 'expenses', 'payments', 'sales', 'purchases'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropConstrainedForeignId('financial_year_id'));
        }
        Schema::dropIfExists('financial_year_account_balances');
        Schema::dropIfExists('financial_year_stock_balances');
        Schema::dropIfExists('financial_year_party_balances');
        Schema::dropIfExists('financial_years');
    }
};
