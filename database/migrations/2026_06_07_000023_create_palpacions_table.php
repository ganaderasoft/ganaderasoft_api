<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('palpacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_finca_id')->nullable()->constrained('personal_fincas')->onDelete('set null');
            $table->string('tipo', 16)->nullable();
            $table->date('fecha')->nullable();
            $table->foreignId('animal_etapa_id')->constrained('animal_etapa')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('palpacions');
    }
};
