<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SanidadSeeder extends Seeder
{
    public function run(): void
    {
        // Vacunas con descripcion y activa (campos nuevos en migración 000006)
        DB::table('vacunas')->insert(collect([
            ['id' => 1, 'nombre' => 'Aftosa',         'descripcion' => 'Fiebre aftosa — vacunación obligatoria bianual',              'activa' => true],
            ['id' => 2, 'nombre' => 'Rabia',           'descripcion' => 'Rabia bovina — vacunación anual en zonas endémicas',          'activa' => true],
            ['id' => 3, 'nombre' => 'Brucelosis',      'descripcion' => 'Brucelosis bovina — vacunación única en hembras jóvenes',     'activa' => true],
            ['id' => 4, 'nombre' => 'Leptospirosis',   'descripcion' => 'Leptospirosis — vacunación semestral',                        'activa' => true],
            ['id' => 5, 'nombre' => 'Clostridium',     'descripcion' => 'Enfermedades clostridiales — vacunación anual',               'activa' => true],
            ['id' => 6, 'nombre' => 'Estomatitis V',   'descripcion' => 'Estomatitis vesicular — control en épocas de vector',        'activa' => true],
            ['id' => 7, 'nombre' => 'IBR',             'descripcion' => 'Rinotraqueítis infecciosa bovina — vacunación anual',        'activa' => true],
            ['id' => 8, 'nombre' => 'DVB',             'descripcion' => 'Diarrea viral bovina — vacunación anual en reproductoras',   'activa' => true],
        ])->map(fn($item) => array_merge($item, ['created_at' => now(), 'updated_at' => now()]))->toArray());

        // Casas comerciales con activa (campo nuevo en migración 000007)
        DB::table('casa_comercials')->insert(collect([
            ['id' => 1, 'laboratorio' => 'Cala',        'marca_comercial' => 'Aftovac',        'activa' => true],
            ['id' => 2, 'laboratorio' => 'Cala',        'marca_comercial' => 'Ravax',          'activa' => true],
            ['id' => 3, 'laboratorio' => 'Cala',        'marca_comercial' => 'Leptovac',       'activa' => true],
            ['id' => 4, 'laboratorio' => 'Cala',        'marca_comercial' => 'Estomavac',      'activa' => true],
            ['id' => 5, 'laboratorio' => 'Vecol',       'marca_comercial' => 'Aftogan',        'activa' => true],
            ['id' => 6, 'laboratorio' => 'Vecol',       'marca_comercial' => 'Rabigan',        'activa' => true],
            ['id' => 7, 'laboratorio' => 'Vecol',       'marca_comercial' => 'V Estomatitis',  'activa' => true],
            ['id' => 8, 'laboratorio' => 'Agropharma',  'marca_comercial' => 'Delta-PGM',      'activa' => true],
            ['id' => 9, 'laboratorio' => 'LAVERLAM',    'marca_comercial' => 'Combibac R8',    'activa' => true],
            ['id' => 10,'laboratorio' => 'MSD Animal',  'marca_comercial' => 'Bovilis IBR',    'activa' => true],
            ['id' => 11,'laboratorio' => 'Zoetis',      'marca_comercial' => 'Bovi-Shield',    'activa' => true],
        ])->map(fn($item) => array_merge($item, ['created_at' => now(), 'updated_at' => now()]))->toArray());

        // Tabla pivote casa_comercial_vacuna (nombre según migración 000008)
        DB::table('casa_comercial_vacuna')->insert(collect([
            // Aftosa
            ['id' =>  1, 'vacuna_id' => 1, 'casa_comercial_id' => 1,  'dosis_cantidad' => 2.00],
            ['id' =>  2, 'vacuna_id' => 1, 'casa_comercial_id' => 5,  'dosis_cantidad' => 2.00],
            // Rabia
            ['id' =>  3, 'vacuna_id' => 2, 'casa_comercial_id' => 2,  'dosis_cantidad' => 1.00],
            ['id' =>  4, 'vacuna_id' => 2, 'casa_comercial_id' => 6,  'dosis_cantidad' => 1.00],
            // Brucelosis
            ['id' =>  5, 'vacuna_id' => 3, 'casa_comercial_id' => 8,  'dosis_cantidad' => 2.00],
            // Leptospirosis
            ['id' =>  6, 'vacuna_id' => 4, 'casa_comercial_id' => 3,  'dosis_cantidad' => 5.00],
            // Clostridium
            ['id' =>  7, 'vacuna_id' => 5, 'casa_comercial_id' => 9,  'dosis_cantidad' => 5.00],
            // Estomatitis
            ['id' =>  8, 'vacuna_id' => 6, 'casa_comercial_id' => 4,  'dosis_cantidad' => 3.00],
            ['id' =>  9, 'vacuna_id' => 6, 'casa_comercial_id' => 7,  'dosis_cantidad' => 5.00],
            // IBR
            ['id' => 10, 'vacuna_id' => 7, 'casa_comercial_id' => 10, 'dosis_cantidad' => 2.00],
            // DVB
            ['id' => 11, 'vacuna_id' => 8, 'casa_comercial_id' => 11, 'dosis_cantidad' => 2.00],
        ])->map(fn($item) => array_merge($item, ['created_at' => now(), 'updated_at' => now()]))->toArray());
    }
}
