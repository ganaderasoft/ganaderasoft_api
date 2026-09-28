<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lactancia_id')->constrained('lactancias')->onDelete('cascade');
            $table->date('fecha_pesaje')->nullable();
            $table->decimal('pesaje_total', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leches');
    }
};
