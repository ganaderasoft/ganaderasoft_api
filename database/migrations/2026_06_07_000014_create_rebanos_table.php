<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rebanos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->constrained()->onDelete('cascade');
            $table->string('nombre', 25);
            $table->boolean('archivado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rebanos');
    }
};
