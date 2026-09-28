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
        Schema::create('inventario_generals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->constrained('fincas')->onDelete('cascade');
            $table->integer('num_personal')->nullable();
            $table->date('fecha_inventario')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_generals');
    }
};
