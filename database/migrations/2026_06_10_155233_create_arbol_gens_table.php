<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('arbol_gens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hijo_id')->constrained('animals')->onDelete('cascade');
            $table->foreignId('padre_id')->constrained('animals')->onDelete('cascade');
            $table->string('tipo', 10)->nullable();

            // Un animal solo puede tener un Padre y una Madre
            $table->unique(['padre_id', 'hijo_id', 'tipo']);
 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arbol_gens');
    }
};
