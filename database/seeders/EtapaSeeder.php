<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class EtapaSeeder extends Seeder {
    public function run() {
        DB::table('etapas')->insert(collect([
            ['id' => 1, 'nombre' => 'Becerro', 'edad_ini' => 0, 'edad_fin' => 365, 'tipo_animal_id' => 1, 'sexo' => 'M'],
            ['id' => 2, 'nombre' => 'Becerra', 'edad_ini' => 0, 'edad_fin' => 365, 'tipo_animal_id' => 1, 'sexo' => 'H'],
            ['id' => 3, 'nombre' => 'Maute', 'edad_ini' => 365, 'edad_fin' => 730, 'tipo_animal_id' => 1, 'sexo' => 'M'],
            ['id' => 4, 'nombre' => 'Mauta', 'edad_ini' => 365, 'edad_fin' => 730, 'tipo_animal_id' => 1, 'sexo' => 'H'],
            ['id' => 5, 'nombre' => 'Novillo', 'edad_ini' => 730, 'edad_fin' => 913, 'tipo_animal_id' => 1, 'sexo' => 'M'],
            ['id' => 6, 'nombre' => 'Novilla', 'edad_ini' => 730, 'edad_fin' => 913, 'tipo_animal_id' => 1, 'sexo' => 'H'],
            ['id' => 7, 'nombre' => 'Toro', 'edad_ini' => 913, 'edad_fin' => null, 'tipo_animal_id' => 1, 'sexo' => 'M'],
            ['id' => 8, 'nombre' => 'Vaca', 'edad_ini' => 913, 'edad_fin' => null, 'tipo_animal_id' => 1, 'sexo' => 'H'],
            ['id' => 9, 'nombre' => 'Becerro', 'edad_ini' => 0, 'edad_fin' => 365, 'tipo_animal_id' => 2, 'sexo' => 'M'],
            ['id' => 10, 'nombre' => 'Becerra', 'edad_ini' => 0, 'edad_fin' => 365, 'tipo_animal_id' => 2, 'sexo' => 'H'],
            ['id' => 11, 'nombre' => 'Añojo', 'edad_ini' => 365, 'edad_fin' => 730, 'tipo_animal_id' => 2, 'sexo' => 'M'],
            ['id' => 12, 'nombre' => 'Añoja', 'edad_ini' => 365, 'edad_fin' => 913, 'tipo_animal_id' => 2, 'sexo' => 'H'],
            ['id' => 13, 'nombre' => 'Butoro', 'edad_ini' => 730, 'edad_fin' => null, 'tipo_animal_id' => 2, 'sexo' => 'M'],
            ['id' => 14, 'nombre' => 'Bufala', 'edad_ini' => 913, 'edad_fin' => null, 'tipo_animal_id' => 2, 'sexo' => 'H'],
        ])->map(fn($item) => array_merge($item, ['created_at' => now(), 'updated_at' => now()]))->toArray());
    }
}
