<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fincas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propietario_id')->constrained()->onDelete('cascade');
            $table->string('nombre', 25)->nullable();
            $table->string('explotacion_tipo', 20)->nullable();
            $table->boolean('archivado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fincas');
    }
};
