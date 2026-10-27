<?php

namespace App\Services;

use App\Models\{Company,Customer,Expense,FinancialYear,FinancialYearAccountBalance,FinancialYearPartyBalance,FinancialYearStockBalance,Payment,Product,Purchase,Sale,Supplier};
use App\Tenancy\FinancialYearContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialYearService
{
    public function ensureCurrent(Company $company): FinancialYear
    {
        $active=FinancialYear::where('status','active')->orderByDesc('start_date')->first();
        if($active){app(FinancialYearContext::class)->set($active);return $active;}

        $startYear=now()->month>=7?now()->year:now()->year-1;
        $start=Carbon::create($startYear,7,1)->startOfDay();$end=$start->copy()->addYear()->subDay();
        $existing=FinancialYear::whereDate('start_date',$start)->whereDate('end_date',$end)->first();
        if($existing&&$existing->status==='closed')throw ValidationException::withMessages(['financial_year'=>'The current calendar period is already closed and no later financial year is open.']);
        $year=$existing?:FinancialYear::create(['name'=>$startYear.'-'.($startYear+1),'start_date'=>$start,'end_date'=>$end,'status'=>'active']);
        $this->seedOpeningBalances($year);
        app(FinancialYearContext::class)->set($year);
        return $year;
    }

    public function preview(FinancialYear $year): array
    {
        $supplierBalances=Supplier::whereDate('created_at','<=',$year->end_date)->orderBy('name')->get()->map(function($supplier)use($year){$opening=$this->partyOpening($year,'supplier',$supplier->id,(float)$supplier->opening_balance);$purchases=(float)$supplier->purchases()->where('financial_year_id',$year->id)->where('status','completed')->sum('grand_total');$paid=(float)$supplier->payments()->where('financial_year_id',$year->id)->where('payment_type','supplier_payment')->where('is_reversed',false)->sum('amount');return ['party_id'=>$supplier->id,'party'=>$supplier->name,'opening'=>$opening,'movement'=>$purchases-$paid,'closing'=>round($opening+$purchases-$paid,2)];});
        $customerBalances=Customer::whereDate('created_at','<=',$year->end_date)->orderBy('name')->get()->map(function($customer)use($year){$opening=$this->partyOpening($year,'customer',$customer->id,(float)$customer->opening_balance);$sales=(float)$customer->sales()->where('financial_year_id',$year->id)->where('status','completed')->sum('grand_total');$received=(float)$customer->payments()->where('financial_year_id',$year->id)->where('payment_type','customer_payment')->where('is_reversed',false)->sum('amount');return ['party_id'=>$customer->id,'party'=>$customer->name,'opening'=>$opening,'movement'=>$sales-$received,'closing'=>round($opening+$sales-$received,2)];});
        $stockOpenings=FinancialYearStockBalance::where('financial_year_id',$year->id)->get()->keyBy('product_id');
        $stockMovements=\App\Models\StockMovement::where('financial_year_id',$year->id)->orderBy('movement_date')->orderBy('id')->get()->groupBy('product_id');
        $stockBalances=Product::whereDate('created_at','<=',$year->end_date)->orderBy('name')->get()->map(function($product)use($stockOpenings,$stockMovements){
            $opening=$stockOpenings->get($product->id);$movements=$stockMovements->get($product->id,collect());
            $quantity=round((float)($opening?->opening_quantity??0)+(float)$movements->sum('quantity_in')-(float)$movements->sum('quantity_out'),3);
            $cost=(float)($movements->last()?->unit_cost??$opening?->opening_unit_cost??$product->average_cost);
            return ['product_id'=>$product->id,'product'=>$product->name,'sku'=>$product->sku,'closing_quantity'=>$quantity,'unit_cost'=>$cost,'closing_value'=>round($quantity*$cost,2)];
        });
        $accounts=collect(['cash','bank_transfer','card','other'])->map(function($method)use($year){$opening=(float)FinancialYearAccountBalance::where('financial_year_id',$year->id)->where('account_key',$method)->value('opening_balance');$payments=Payment::where('financial_year_id',$year->id)->where('payment_method',$method)->where('is_reversed',false)->get();$received=(float)$payments->where('payment_type','customer_payment')->sum('amount');$paid=(float)$payments->where('payment_type','!=','customer_payment')->sum('amount');return ['account_key'=>$method,'opening'=>$opening,'received'=>$received,'paid'=>$paid,'closing'=>round($opening+$received-$paid,2)];});
        return ['supplier_balances'=>$supplierBalances,'customer_balances'=>$customerBalances,'stock_balances'=>$stockBalances,'account_balances'=>$accounts,
            'summary'=>['supplier_payables'=>$supplierBalances->sum('closing'),'customer_receivables'=>$customerBalances->sum('closing'),'inventory_quantity'=>$stockBalances->sum('closing_quantity'),'inventory_value'=>$stockBalances->sum('closing_value'),'cash_bank_balance'=>$accounts->sum('closing'),
                'purchases'=>(float)Purchase::where('financial_year_id',$year->id)->where('status','completed')->sum('grand_total'),'sales'=>(float)Sale::where('financial_year_id',$year->id)->where('status','completed')->sum('grand_total'),'expenses'=>(float)Expense::where('financial_year_id',$year->id)->where('status','posted')->sum('amount')]];
    }

    public function close(FinancialYear $year,int $userId): FinancialYear
    {
        return DB::transaction(function()use($year,$userId){
            $year=FinancialYear::query()->lockForUpdate()->findOrFail($year->id);
            if($year->status!=='active')throw ValidationException::withMessages(['financial_year'=>'This financial year is already closed.']);
            if(today()->lt($year->end_date))throw ValidationException::withMessages(['financial_year'=>'This financial year cannot be closed before '.$year->end_date->format('d M Y').'.']);
            $preview=$this->preview($year);$nextStart=$year->end_date->copy()->addDay();$nextEnd=$nextStart->copy()->addYear()->subDay();
            $year->update(['status'=>'closed','closed_at'=>now(),'closed_by'=>$userId,'closing_summary'=>$preview['summary']]);
            $next=FinancialYear::create(['name'=>$nextStart->year.'-'.$nextEnd->year,'start_date'=>$nextStart,'end_date'=>$nextEnd,'status'=>'active']);
            foreach($preview['supplier_balances'] as $row){FinancialYearPartyBalance::create(['financial_year_id'=>$next->id,'party_type'=>'supplier','party_id'=>$row['party_id'],'opening_balance'=>$row['closing']]);Supplier::whereKey($row['party_id'])->update(['opening_balance'=>$row['closing']]);}
            foreach($preview['customer_balances'] as $row){FinancialYearPartyBalance::create(['financial_year_id'=>$next->id,'party_type'=>'customer','party_id'=>$row['party_id'],'opening_balance'=>$row['closing']]);Customer::whereKey($row['party_id'])->update(['opening_balance'=>$row['closing']]);}
            foreach($preview['stock_balances'] as $row)FinancialYearStockBalance::create(['financial_year_id'=>$next->id,'product_id'=>$row['product_id'],'opening_quantity'=>$row['closing_quantity'],'opening_unit_cost'=>$row['unit_cost'],'opening_value'=>$row['closing_value']]);
            foreach($preview['account_balances'] as $row)FinancialYearAccountBalance::create(['financial_year_id'=>$next->id,'account_key'=>$row['account_key'],'opening_balance'=>$row['closing']]);
            app(FinancialYearContext::class)->set($next);
            return $year->fresh(['closedBy:id,name']);
        });
    }

    public function updateAccountOpenings(FinancialYear $year,array $balances):array
    {
        DB::transaction(function()use($year,$balances){$locked=$this->lockActive();if($locked->id!==$year->id)throw ValidationException::withMessages(['financial_year'=>'Only the current open financial year can be adjusted.']);foreach($balances as $row)FinancialYearAccountBalance::updateOrCreate(['financial_year_id'=>$year->id,'account_key'=>$row['account_key']],['opening_balance'=>$row['opening_balance']]);});
        return $this->preview($year);
    }

    public function lockActive():FinancialYear
    {
        $year=FinancialYear::query()->lockForUpdate()->findOrFail(app(FinancialYearContext::class)->requireId());
        if($year->status!=='active')throw ValidationException::withMessages(['financial_year'=>'The financial year was closed before this entry could be posted. Refresh and use the new session.']);
        return $year;
    }

    public function partyOpening(FinancialYear $year,string $type,int $partyId,float $fallback=0):float
    {
        $value=FinancialYearPartyBalance::where('financial_year_id',$year->id)->where('party_type',$type)->where('party_id',$partyId)->value('opening_balance');
        return $value===null?($year->status==='active'?$fallback:0):(float)$value;
    }

    private function seedOpeningBalances(FinancialYear $year):void
    {
        foreach(Supplier::all() as $supplier)FinancialYearPartyBalance::firstOrCreate(['financial_year_id'=>$year->id,'party_type'=>'supplier','party_id'=>$supplier->id],['opening_balance'=>(float)$supplier->opening_balance]);
        foreach(Customer::all() as $customer)FinancialYearPartyBalance::firstOrCreate(['financial_year_id'=>$year->id,'party_type'=>'customer','party_id'=>$customer->id],['opening_balance'=>(float)$customer->opening_balance]);
        foreach(Product::all() as $product)FinancialYearStockBalance::firstOrCreate(['financial_year_id'=>$year->id,'product_id'=>$product->id],['opening_quantity'=>(float)$product->current_stock,'opening_unit_cost'=>(float)$product->average_cost,'opening_value'=>round((float)$product->current_stock*(float)$product->average_cost,2)]);
        foreach(['cash','bank_transfer','card','other'] as $method)FinancialYearAccountBalance::firstOrCreate(['financial_year_id'=>$year->id,'account_key'=>$method],['opening_balance'=>0]);
    }
}
