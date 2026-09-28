<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casa_comercials', function (Blueprint $table) {
            $table->id();
            $table->string('laboratorio', 30);
            $table->string('marca_comercial', 25);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casa_comercials');
    }
};
