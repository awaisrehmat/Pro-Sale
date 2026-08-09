<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_code', 10);
            $table->string('period', 4);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['document_code', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_number_sequences');
    }
};
