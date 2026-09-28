<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peso_corporals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_etapa_id')->constrained('animal_etapa')->onDelete('cascade');
            $table->date('fecha_peso')->nullable();
            $table->float('peso')->nullable();
            $table->string('comentario', 40)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peso_corporals');
    }
};
