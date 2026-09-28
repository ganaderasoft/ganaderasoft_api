<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('cedula')->unique();
            $table->string('nombre');
            $table->string('apellido')->nullable();
            $table->string('telefono')->nullable();
            $table->string('correo')->unique()->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('status', ['activo', 'inactivo'])->default('activo');
             $table->timestamps();
        });

        // Add domain constraint (MySQL 8.0.16+ / MariaDB 10.2.1+)
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE personas ADD CONSTRAINT chk_cedula_format CHECK (cedula REGEXP '^[VGEJ][0-9]+$')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
