<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terrenos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->constrained()->onDelete('cascade');
            $table->float('superficie')->nullable();
            $table->string('relieve', 9)->nullable();
            $table->string('suelo_textura', 25)->nullable();
            $table->char('ph_suelo', 2)->nullable();
            $table->float('precipitacion')->nullable();
            $table->float('velocidad_viento')->nullable();
            $table->string('temp_anual', 4)->nullable();
            $table->string('temp_min', 4)->nullable();
            $table->string('temp_max', 4)->nullable();
            $table->float('radiacion')->nullable();
            $table->string('fuente_agua', 25)->nullable();
            $table->integer('caudal_disponible')->nullable();
            $table->string('riego_metodo', 18)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terrenos');
    }
};
