<?php

namespace Tests\Feature\Legacy;

use Tests\TestCase;
use App\Models\User;
use App\Models\Finca;
use App\Models\Propietario;
use App\Models\InventarioBufalo;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InventarioBufaloLegacyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup without API version header to trigger legacy behavior
        $this->withHeaders([
            'Accept' => 'application/json',
        ]);
    }

    protected function createAdminUser()
    {
        $user = User::factory()->create();
        $role = \App\Models\Role::firstOrCreate(['code' => 'admin', 'name' => 'Admin']);
        $user->roles()->attach($role);
        return $user;
    }

    protected function createFinca($attributes = [])
    {
        $persona = \App\Models\Persona::create(['nombre' => 'Test', 'apellido' => 'Test', 'cedula' => 'V' . rand(1000000, 9999999)]);
        $propietarioId = $attributes['propietario_id'] ?? Propietario::create(['persona_id' => $persona->id])->id;
        
        return Finca::create(array_merge([
            'nombre' => 'Test Finca',
            'propietario_id' => $propietarioId,
        ], $attributes));
    }

    protected function createInventario($attributes = [])
    {
        return InventarioBufalo::create(array_merge([
            'fecha_inventario' => '2023-10-01',
        ], $attributes));
    }

    public function test_legacy_can_list_inventarios_bufalo()
    {
        $admin = $this->createAdminUser();
        $finca = $this->createFinca();
        $this->createInventario(['finca_id' => $finca->id]);
        $this->createInventario(['finca_id' => $finca->id]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/inventarios-bufalo');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['data' => [['id_InvBufalo', 'id_Finca']]]]);
    }

    public function test_legacy_can_create_inventario_bufalo()
    {
        $admin = $this->createAdminUser();
        $finca = $this->createFinca();

        $data = [
            'id_Finca' => $finca->id,
            'Num_Becerro' => 4,
            'Fecha_Inventario' => '2023-10-01'
        ];

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/inventarios-bufalo', $data);

        $response->assertStatus(201)
                 ->assertJsonStructure(['data' => ['id_InvBufalo', 'id_Finca', 'Num_Becerro']]);
    }

    public function test_legacy_can_show_inventario_bufalo()
    {
        $admin = $this->createAdminUser();
        $finca = $this->createFinca();
        $inventario = $this->createInventario(['finca_id' => $finca->id]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/inventarios-bufalo/' . $inventario->id);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['id_InvBufalo', 'id_Finca']]);
    }

    public function test_legacy_can_update_inventario_bufalo()
    {
        $admin = $this->createAdminUser();
        $finca = $this->createFinca();
        $inventario = $this->createInventario(['finca_id' => $finca->id, 'num_becerro' => 2]);

        $data = [
            'Num_Becerro' => 8,
        ];

        $response = $this->actingAs($admin, 'sanctum')->putJson('/api/inventarios-bufalo/' . $inventario->id, $data);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['id_InvBufalo', 'Num_Becerro']]);
    }

    public function test_legacy_cannot_find_inventario()
    {
        $admin = $this->createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/inventarios-bufalo/999999');
        $response->assertStatus(404);
    }
}
