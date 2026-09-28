<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimiento_rebanos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->constrained()->onDelete('cascade');
            $table->foreignId('rebano_id')->constrained()->onDelete('cascade');
            $table->string('rebano_destino', 30)->nullable();
            $table->integer('finca_destino_id')->nullable();
            $table->integer('rebano_destino_id')->nullable();
            $table->string('comentario', 40)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimiento_rebanos');
    }
};
