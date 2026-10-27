<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $tables = ['purchases','sales','payments','expenses','stock_movements','stock_adjustments'];

    public function up():void
    {
        foreach($this->tables as $tableName)Schema::table($tableName,function(Blueprint $table){$table->unsignedBigInteger('financial_year_id')->nullable(false)->change();});
    }

    public function down():void
    {
        foreach($this->tables as $tableName)Schema::table($tableName,function(Blueprint $table){$table->unsignedBigInteger('financial_year_id')->nullable()->change();});
    }
};
