<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medidas_corporales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_etapa_id')->constrained('animal_etapa')->onDelete('cascade');
            $table->float('altura_hc')->nullable();
            $table->float('altura_hg')->nullable();
            $table->float('perimetro_pt')->nullable();
            $table->float('perimetro_pca')->nullable();
            $table->float('longitud_lc')->nullable();
            $table->float('longitud_lg')->nullable();
            $table->float('anchura_ag')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medidas_corporales');
    }
};
