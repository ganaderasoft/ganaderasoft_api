<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etapas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 20)->nullable();
            $table->integer('edad_ini')->nullable();
            $table->integer('edad_fin')->nullable();
            $table->foreignId('tipo_animal_id')->constrained('tipo_animals')->onDelete('cascade');
            $table->char('sexo', 1)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etapas');
    }
};
