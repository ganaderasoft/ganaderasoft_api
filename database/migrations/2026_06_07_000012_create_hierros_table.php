<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hierros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->constrained()->onDelete('cascade');
            $table->foreignId('propietario_id')->constrained()->onDelete('cascade');
            $table->string('identificador', 10)->nullable();
            $table->string('hierro_imagen', 255)->nullable();
            $table->string('hierro_qr', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hierros');
    }
};
