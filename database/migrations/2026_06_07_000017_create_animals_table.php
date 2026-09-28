<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rebano_id')->constrained()->onDelete('cascade');
            $table->string('nombre', 25)->nullable();
            $table->string('codigo_animal', 20)->nullable();
            $table->char('sexo', 1)->nullable();
            $table->date('fecha_nacimiento');
            $table->string('procedencia', 50)->nullable();
            $table->boolean('archivado')->default(false);
            $table->foreignId('composicion_raza_id')->constrained('composicion_razas')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
