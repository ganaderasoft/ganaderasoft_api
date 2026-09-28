<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Administrador Global ──────────────────────────────────────────────
        $personaAdminId = DB::table('personas')->insertGetId([
            'cedula'     => 'V00000001',
            'nombre'     => 'Admin',
            'apellido'   => 'Sistema',
            'telefono'   => '04140000001',
            'status'     => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userAdminId = DB::table('users')->insertGetId([
            'name'       => 'Administrador',
            'email'      => 'admin1@ucv.com',
            'password'   => Hash::make('123456789'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('persona_user')->insert([
            'user_id'    => $userAdminId,
            'persona_id' => $personaAdminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_user')->insert([
            'user_id'    => $userAdminId,
            'role_id'    => 1, // global_admin
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('administradors')->insert([
            'persona_id' => $personaAdminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── Propietario de Ejemplo ───────────────────────────────────────────
        $personaPropietarioId = DB::table('personas')->insertGetId([
            'cedula'     => 'V12345678',
            'nombre'     => 'Juan',
            'apellido'   => 'Pérez',
            'telefono'   => '04141234567',
            'status'     => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userPropietarioId = DB::table('users')->insertGetId([
            'name'       => 'Juan Pérez',
            'email'      => 'propietario1@ucv.com',
            'password'   => Hash::make('123456789'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('persona_user')->insert([
            'user_id'    => $userPropietarioId,
            'persona_id' => $personaPropietarioId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_user')->insert([
            'user_id'    => $userPropietarioId,
            'role_id'    => 2, // propietario
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('propietarios')->insert([
            'persona_id' => $personaPropietarioId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
