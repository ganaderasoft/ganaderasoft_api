<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class TipoAnimalSeeder extends Seeder {
    public function run() {
        DB::table('tipo_animals')->insert(collect([
            ['id' => 1, 'nombre' => 'Vacuno'],
            ['id' => 2, 'nombre' => 'Bufala'],
        ])->map(fn($item) => array_merge($item, ['created_at' => now(), 'updated_at' => now()]))->toArray());
    }
}
