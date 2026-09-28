<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Core de Seguridad
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,

            // 2. Catálogos del Sistema (No dependen del usuario)
            TipoAnimalSeeder::class,
            TipoTrabajadorSeeder::class,
            EstadoSaludSeeder::class,
            EtapaSeeder::class,
            ComposicionRazaSeeder::class,
            
            // 3. Catálogos Reproductivos / Sanidad
            DiaPalpacionSeeder::class,
            FoliculoSeeder::class,
            SanidadSeeder::class,
        ]);
    }
}
