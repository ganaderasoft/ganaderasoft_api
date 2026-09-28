<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenez_dias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dia_palpacion_id')->constrained('dia_palpacions')->onDelete('cascade');
            $table->foreignId('palpacion_id')->constrained('palpacions')->onDelete('cascade');
            $table->decimal('tamano', 6, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenez_dias');
    }
};
