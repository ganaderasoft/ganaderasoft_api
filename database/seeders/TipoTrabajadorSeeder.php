<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class TipoTrabajadorSeeder extends Seeder {
    public function run() {
        DB::table('tipo_trabajadors')->insert(collect([
            ['id' => 1, 'nombre' => 'Administrador'],
            ['id' => 3, 'nombre' => 'Operario'],
            ['id' => 4, 'nombre' => 'Inseminador'],
            ['id' => 5, 'nombre' => 'Veterinario'],
        ])->map(fn($item) => array_merge($item, ['created_at' => now(), 'updated_at' => now()]))->toArray());
    }
}
