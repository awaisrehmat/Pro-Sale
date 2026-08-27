<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('expense_number');
            $table->date('expense_date')->index();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->string('payee_name');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 30);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('posted')->index();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'expense_number']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('expense_id')->nullable()->after('sale_id')->constrained()->nullOnDelete();
            $table->string('payee_name')->nullable()->after('customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_id');
            $table->dropColumn('payee_name');
        });
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
