<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indice_corporals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_etapa_id')->constrained('animal_etapa')->onDelete('cascade');
            $table->float('anamorfosis')->nullable();
            $table->float('corporal')->nullable();
            $table->float('pelviano')->nullable();
            $table->float('proporcionalidad')->nullable();
            $table->float('dactilo_toracico')->nullable();
            $table->float('pelviano_transversal')->nullable();
            $table->float('pelviano_longitudinal')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indice_corporals');
    }
};
