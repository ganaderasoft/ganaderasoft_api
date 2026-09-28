<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_fincas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->constrained()->onDelete('cascade');
            $table->foreignId('persona_id')->constrained('personas')->onDelete('cascade');
            $table->foreignId('tipo_trabajador_id')->constrained('tipo_trabajadors')->onDelete('cascade');
            $table->enum('status', ['activo', 'inactivo'])->default('activo');
            $table->date('fecha_ingreso')->nullable();
            $table->unique(['finca_id', 'persona_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_fincas');
    }
};
