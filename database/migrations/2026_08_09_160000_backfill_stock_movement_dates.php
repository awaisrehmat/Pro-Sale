<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('stock_movements')->where('movement_type', 'purchase')->orderBy('id')->each(function ($movement) {
            $date = DB::table('purchases')->where('id', $movement->reference_id)->value('purchase_date');
            if ($date) DB::table('stock_movements')->where('id', $movement->id)->update(['movement_date' => $date]);
        });
        DB::table('stock_movements')->where('movement_type', 'sale')->orderBy('id')->each(function ($movement) {
            $date = DB::table('sales')->where('id', $movement->reference_id)->value('sale_date');
            if ($date) DB::table('stock_movements')->where('id', $movement->id)->update(['movement_date' => $date]);
        });
        DB::table('stock_movements')->whereIn('movement_type', ['positive_adjustment','negative_adjustment'])->orderBy('id')->each(function ($movement) {
            $date = DB::table('stock_adjustments')->where('id', $movement->reference_id)->value('adjustment_date');
            if ($date) DB::table('stock_movements')->where('id', $movement->id)->update(['movement_date' => $date]);
        });
    }

    public function down(): void {}
};
