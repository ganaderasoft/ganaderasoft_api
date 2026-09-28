<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_celos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_etapa_id')->constrained('animal_etapa')->onDelete('cascade');
            $table->date('fecha')->nullable();
            $table->string('observacion', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_celos');
    }
};
