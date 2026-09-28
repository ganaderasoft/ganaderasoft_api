<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuernos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('palpacion_id')->constrained()->onDelete('cascade');
            $table->float('tamano')->nullable();
            $table->string('medicion', 2)->nullable();
            $table->char('lado', 3)->nullable();
            $table->string('iu_plano', 3)->nullable();
            $table->string('estado_sano', 12)->nullable();
            $table->string('patologia_nombre', 40)->nullable();
            $table->string('patologia_descripcion', 80)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuernos');
    }
};
