<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('units_of_measurement', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('symbol', 20)->unique();
            $table->unsignedTinyInteger('decimal_places')->default(3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('unit_of_measurement_id')->nullable()->after('product_subcategory_id')->constrained('units_of_measurement')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropConstrainedForeignId('unit_of_measurement_id'));
        Schema::dropIfExists('units_of_measurement');
    }
};
