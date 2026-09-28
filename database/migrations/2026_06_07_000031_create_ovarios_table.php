<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ovarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('palpacion_id')->constrained()->onDelete('cascade');
            $table->char('medida', 2)->nullable();
            $table->decimal('tamano', 6, 2)->nullable();
            $table->char('lado', 3)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ovarios');
    }
};
