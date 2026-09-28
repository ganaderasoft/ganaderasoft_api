<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foliculo_ovario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foliculo_id')->constrained('foliculos')->onDelete('cascade');
            $table->foreignId('ovario_id')->constrained('ovarios')->onDelete('cascade');
            $table->decimal('tamano', 6, 2);
            $table->string('fase', 10);
            $table->integer('cantidad')->default(1);
            
            $table->unique(['foliculo_id', 'ovario_id', 'tamano', 'fase'], 'idx_foliculo_ovario_unique');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foliculo_ovario');
    }
};
