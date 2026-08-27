<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['group_id', 'code']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->boolean('is_group_admin')->default(false)->after('password');
        });
        Schema::create('company_user', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['company_id', 'user_id']);
        });

        $tables = ['products','suppliers','customers','purchases','purchase_items','sales','sale_items','stock_movements','stock_adjustments','payments','settings','product_categories','product_subcategories','units_of_measurement','document_number_sequences'];
        foreach ($tables as $name) Schema::table($name, fn (Blueprint $table) => $table->foreignId('company_id')->after('id')->constrained()->cascadeOnDelete());

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['sku']); $table->dropUnique(['barcode']);
            $table->unique(['company_id','sku']); $table->unique(['company_id','barcode']);
        });
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropUnique(['name']); $table->unique(['company_id','name']);
        });
        Schema::table('units_of_measurement', function (Blueprint $table) {
            $table->dropUnique(['name']); $table->dropUnique(['symbol']);
            $table->unique(['company_id','name']); $table->unique(['company_id','symbol']);
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['purchase_number']); $table->unique(['company_id','purchase_number']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['sale_number']); $table->unique(['company_id','sale_number']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['payment_number']); $table->unique(['company_id','payment_number']);
        });
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['key']); $table->unique(['company_id','key']);
        });
        Schema::table('document_number_sequences', function (Blueprint $table) {
            $table->dropUnique(['document_code','period']); $table->unique(['company_id','document_code','period']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('This tenancy migration is intended for a fresh database.');
    }
};
