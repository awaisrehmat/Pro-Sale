<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $t) {
            $t->id(); $t->string('name')->index(); $t->string('sku')->unique(); $t->string('barcode')->nullable()->unique();
            $t->text('description')->nullable(); $t->string('unit', 30)->default('piece');
            $t->decimal('purchase_price', 15, 2)->default(0); $t->decimal('sale_price', 15, 2)->default(0);
            $t->decimal('average_cost', 15, 2)->default(0); $t->decimal('minimum_stock_level', 15, 3)->default(0);
            $t->decimal('current_stock', 15, 3)->default(0); $t->boolean('is_active')->default(true)->index();
            $t->timestamps(); $t->softDeletes();
        });
        Schema::create('suppliers', function (Blueprint $t) {
            $t->id(); $t->string('name')->index(); $t->string('contact_person')->nullable(); $t->string('phone')->nullable()->index();
            $t->string('email')->nullable(); $t->text('address')->nullable(); $t->decimal('opening_balance', 15, 2)->default(0);
            $t->text('notes')->nullable(); $t->boolean('is_active')->default(true)->index(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('customers', function (Blueprint $t) {
            $t->id(); $t->string('name')->index(); $t->string('phone')->nullable()->index(); $t->string('email')->nullable();
            $t->text('address')->nullable(); $t->decimal('opening_balance', 15, 2)->default(0); $t->text('notes')->nullable();
            $t->boolean('is_walk_in')->default(false); $t->boolean('is_active')->default(true)->index(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('purchases', function (Blueprint $t) {
            $t->id(); $t->string('purchase_number')->unique(); $t->date('purchase_date')->index(); $t->foreignId('supplier_id')->constrained();
            $t->string('supplier_invoice_number')->nullable(); $t->string('payment_method', 30); $t->decimal('subtotal',15,2);
            $t->decimal('discount',15,2)->default(0); $t->decimal('additional_cost',15,2)->default(0); $t->decimal('grand_total',15,2);
            $t->decimal('paid_amount',15,2)->default(0); $t->decimal('due_amount',15,2); $t->string('payment_status',20)->index();
            $t->string('status',20)->default('completed')->index(); $t->text('notes')->nullable(); $t->foreignId('created_by')->constrained('users'); $t->timestamps();
        });
        Schema::create('purchase_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('purchase_id')->constrained()->cascadeOnDelete(); $t->foreignId('product_id')->constrained();
            $t->decimal('quantity',15,3); $t->decimal('unit_price',15,2); $t->decimal('discount',15,2)->default(0);
            $t->decimal('line_total',15,2); $t->decimal('previous_average_cost',15,2)->default(0); $t->timestamps();
        });
        Schema::create('sales', function (Blueprint $t) {
            $t->id(); $t->string('sale_number')->unique(); $t->date('sale_date')->index(); $t->foreignId('customer_id')->constrained();
            $t->decimal('subtotal',15,2); $t->decimal('discount',15,2)->default(0); $t->decimal('tax',15,2)->default(0);
            $t->decimal('grand_total',15,2); $t->decimal('paid_amount',15,2)->default(0); $t->decimal('due_amount',15,2);
            $t->string('payment_method',30); $t->string('payment_status',20)->index(); $t->string('status',20)->default('completed')->index();
            $t->text('notes')->nullable(); $t->foreignId('created_by')->constrained('users'); $t->timestamps();
        });
        Schema::create('sale_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('sale_id')->constrained()->cascadeOnDelete(); $t->foreignId('product_id')->constrained();
            $t->decimal('quantity',15,3); $t->decimal('unit_price',15,2); $t->decimal('discount',15,2)->default(0);
            $t->decimal('line_total',15,2); $t->decimal('unit_cost',15,2); $t->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id(); $t->foreignId('product_id')->constrained(); $t->date('movement_date')->index(); $t->string('movement_type',30)->index();
            $t->nullableMorphs('reference'); $t->string('reference_number')->nullable()->index(); $t->decimal('quantity_in',15,3)->default(0);
            $t->decimal('quantity_out',15,3)->default(0); $t->decimal('stock_before',15,3); $t->decimal('stock_after',15,3);
            $t->decimal('unit_cost',15,2)->default(0); $t->text('notes')->nullable(); $t->foreignId('created_by')->constrained('users'); $t->timestamps();
        });
        Schema::create('stock_adjustments', function (Blueprint $t) {
            $t->id(); $t->foreignId('product_id')->constrained(); $t->date('adjustment_date')->index(); $t->string('adjustment_type',20);
            $t->decimal('quantity',15,3); $t->string('reason'); $t->foreignId('created_by')->constrained('users'); $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id(); $t->string('payment_number')->unique(); $t->date('payment_date')->index(); $t->string('payment_type',30)->index();
            $t->foreignId('supplier_id')->nullable()->constrained(); $t->foreignId('customer_id')->nullable()->constrained();
            $t->foreignId('purchase_id')->nullable()->constrained(); $t->foreignId('sale_id')->nullable()->constrained();
            $t->decimal('amount',15,2); $t->string('payment_method',30); $t->string('reference_number')->nullable();
            $t->text('notes')->nullable(); $t->boolean('is_reversed')->default(false); $t->foreignId('created_by')->constrained('users'); $t->timestamps();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->id(); $t->string('key')->unique(); $t->text('value')->nullable(); $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings'); Schema::dropIfExists('payments'); Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_movements'); Schema::dropIfExists('sale_items'); Schema::dropIfExists('sales');
        Schema::dropIfExists('purchase_items'); Schema::dropIfExists('purchases'); Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers'); Schema::dropIfExists('products');
    }
};
