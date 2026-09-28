<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casa_comercial_vacuna', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_comercial_id')->constrained()->onDelete('cascade');
            $table->foreignId('vacuna_id')->constrained()->onDelete('cascade');
            $table->decimal('dosis_cantidad', 10, 2);
            
            $table->unique(['casa_comercial_id', 'vacuna_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casa_comercial_vacuna');
    }
};
