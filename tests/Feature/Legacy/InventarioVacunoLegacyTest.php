<?php

namespace Tests\Feature\Legacy;

use App\Models\Finca;
use App\Models\InventarioVacuno;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventarioVacunoLegacyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function createAdminUser()
    {
        $role = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
        $user = User::firstOrCreate(
            ['email' => 'admin_legacy@example.com'],
            ['name' => 'Admin User Legacy', 'password' => bcrypt('password')]
        );
        if (!$user->roles->contains($role->id)) {
            $user->roles()->attach($role->id);
        }
        return $user;
    }

    private function getFinca()
    {
        return Finca::firstOrCreate(
            ['nombre' => 'Finca Legacy Test'],
            ['propietario_id' => 1]
        );
    }

    public function test_legacy_index_format()
    {
        $admin = $this->createAdminUser();
        $finca = $this->getFinca();
        InventarioVacuno::create(['finca_id' => $finca->id, 'num_vaca' => 5]);

        $response = $this->actingAs($admin)
            ->getJson('/api/inventario-vacuno');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id_InvVacuno', 'id_Finca', 'Num_Vaca']
                ],
                'meta'
            ]);
    }

    public function test_legacy_store_format()
    {
        $admin = $this->createAdminUser();
        $finca = $this->getFinca();

        $response = $this->actingAs($admin)
            ->postJson('/api/inventario-vacuno', [
                'id_Finca' => $finca->id,
                'Num_Vaca' => 10,
                'Num_Toro' => 2,
                'Fecha_Inventario' => '2026-07-01'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id_InvVacuno', 'id_Finca', 'Num_Vaca']
            ])
            ->assertJsonPath('data.Num_Vaca', 10);
    }
    
    public function test_legacy_show_format()
    {
        $admin = $this->createAdminUser();
        $finca = $this->getFinca();
        $inv = InventarioVacuno::create(['finca_id' => $finca->id, 'num_vaca' => 7]);

        $response = $this->actingAs($admin)
            ->getJson('/api/inventario-vacuno/' . $inv->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id_InvVacuno', 'id_Finca', 'Num_Vaca']
            ]);
    }

    public function test_legacy_update_format()
    {
        $admin = $this->createAdminUser();
        $finca = $this->getFinca();
        $inv = InventarioVacuno::create(['finca_id' => $finca->id, 'num_vaca' => 8]);

        $response = $this->actingAs($admin)
            ->putJson('/api/inventario-vacuno/' . $inv->id, [
                'Num_Vaca' => 25
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.Num_Vaca', 25);
    }
}
