<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventario_vacunos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->constrained('fincas')->onDelete('cascade');
            $table->integer('num_becerra')->nullable();
            $table->integer('num_mauta')->nullable();
            $table->integer('num_novilla')->nullable();
            $table->integer('num_vaca')->nullable();
            $table->integer('num_becerro')->nullable();
            $table->integer('num_maute')->nullable();
            $table->integer('num_torete')->nullable();
            $table->integer('num_toro')->nullable();
            $table->date('fecha_inventario')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_vacunos');
    }
};
