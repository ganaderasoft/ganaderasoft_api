<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla: animal_vacuna
     * Registro individual de vacunas aplicadas a cada animal.
     */
    public function up(): void
    {
        Schema::create('animal_vacuna', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->onDelete('cascade');
            $table->foreignId('vacuna_id')->constrained('vacunas')->onDelete('cascade');
            $table->foreignId('persona_id')->nullable()->constrained('personas')->onDelete('set null');
            $table->date('fecha');
            $table->decimal('dosis', 10, 2)->nullable();
            $table->decimal('costo', 10, 2)->default(0);
            $table->string('lote', 50)->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->unique(['animal_id', 'vacuna_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_vacuna');
    }
};
