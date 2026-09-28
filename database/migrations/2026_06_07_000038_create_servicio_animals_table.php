<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicio_animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->onDelete('cascade');
            $table->foreignId('semen_toro_id')->nullable()->constrained('semen_toros')->onDelete('set null');
            $table->foreignId('personal_finca_id')->nullable()->constrained('personal_fincas')->onDelete('set null');
            $table->foreignId('registro_celo_id')->nullable()->constrained('registro_celos')->onDelete('set null');
            $table->string('tipo', 11)->nullable();
            $table->date('fecha')->nullable();
            $table->string('observacion', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_animals');
    }
};
