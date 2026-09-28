<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('composicion_razas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30)->nullable();
            $table->string('siglas', 6)->nullable();
            $table->string('pelaje', 80)->nullable();
            $table->string('proposito', 15)->nullable();
            $table->text('tipo_raza')->nullable();
            $table->string('origen', 60)->nullable();
            $table->string('caracteristica_especial', 80)->nullable();
            $table->string('proporcion_raza', 20)->nullable();
            $table->foreignId('finca_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('tipo_animal_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composicion_razas');
    }
};
