<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class EstadoSaludSeeder extends Seeder {
    public function run() {
        DB::table('estado_saluds')->insert(collect([
            ['id' => 1, 'nombre' => 'Sano'],
            ['id' => 2, 'nombre' => 'Enfermo'],
            ['id' => 3, 'nombre' => 'Muerto'],
            ['id' => 4, 'nombre' => 'Servicio'],
            ['id' => 5, 'nombre' => 'Gestacion'],
        ])->map(fn($item) => array_merge($item, ['created_at' => now(), 'updated_at' => now()]))->toArray());
    }
}
