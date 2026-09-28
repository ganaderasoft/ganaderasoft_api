<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_etapa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->onDelete('cascade');
            $table->foreignId('etapa_id')->constrained()->onDelete('cascade');
            $table->date('fecha_ini');
            $table->date('fecha_fin')->nullable();
            
            $table->unique(['animal_id', 'etapa_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_etapa');
    }
};
