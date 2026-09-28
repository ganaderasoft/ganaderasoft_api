<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class FoliculoSeeder extends Seeder {
    public function run() {
        DB::table('foliculos')->insert(collect([
            ['id' => 1, 'nombre' => 'Foliculo Ovario Derecho', 'siglas' => 'FOD'],
            ['id' => 2, 'nombre' => 'Foliculo Ovario Izquierdo', 'siglas' => 'FOI'],
            ['id' => 3, 'nombre' => 'Cuerpo Luteo Ovarico Derecho', 'siglas' => 'CLOD'],
            ['id' => 4, 'nombre' => 'Cuerpo Luteo Ovarico Izquierdo', 'siglas' => 'CLOI'],
            ['id' => 5, 'nombre' => 'Sin estructura Palpable', 'siglas' => 'SEP'],
        ])->map(fn($item) => array_merge($item, ['created_at' => now(), 'updated_at' => now()]))->toArray());
    }
}
