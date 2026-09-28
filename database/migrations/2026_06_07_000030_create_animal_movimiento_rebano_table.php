<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_movimiento_rebano', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->onDelete('cascade');
            $table->foreignId('movimiento_rebano_id')->constrained('movimiento_rebanos')->onDelete('cascade');
            $table->string('estado', 9)->nullable();
            
            $table->unique(['animal_id', 'movimiento_rebano_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_movimiento_rebano');
    }
};
