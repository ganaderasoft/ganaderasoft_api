<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class DiaPalpacionSeeder extends Seeder {
    public function run() {
        DB::table('dia_palpacions')->insert(collect([
            ['id' => 1, 'dias' => '30d'],
            ['id' => 2, 'dias' => '60d'],
            ['id' => 3, 'dias' => '90d'],
            ['id' => 4, 'dias' => '120d'],
            ['id' => 5, 'dias' => '150d'],
            ['id' => 6, 'dias' => '180d'],
            ['id' => 7, 'dias' => '270d'],
        ])->map(fn($item) => array_merge($item, ['created_at' => now(), 'updated_at' => now()]))->toArray());
    }
}
