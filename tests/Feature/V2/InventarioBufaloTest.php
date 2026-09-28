<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use App\Models\User;
use App\Models\Finca;
use App\Models\Propietario;
use App\Models\InventarioBufalo;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InventarioBufaloTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup common headers for V2
        $this->withHeaders([
            'X-API-VERSION' => '2',
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

    protected function createPropietarioUser()
    {
        $user = User::factory()->create();
        $role = \App\Models\Role::firstOrCreate(['code' => 'propietario', 'name' => 'Propietario']);
        $user->roles()->attach($role);

        $persona = \App\Models\Persona::create(['nombre' => 'Test', 'apellido' => 'Persona', 'cedula' => 'V' . rand(1000000, 9999999)]);
        $user->personas()->attach($persona);
        $propietario = Propietario::create(['persona_id' => $persona->id]);

        return [$user, $propietario];
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

    public function test_can_list_inventarios_bufalo_as_admin()
    {
        $admin = $this->createAdminUser();
        $finca = $this->createFinca();
        $this->createInventario(['finca_id' => $finca->id]);
        $this->createInventario(['finca_id' => $finca->id]);
        $this->createInventario(['finca_id' => $finca->id]);
        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/inventarios-bufalo');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['data' => [['id', 'finca_id', 'total_bufalos']]]]);
    }

    public function test_can_list_inventarios_bufalo_nopaginate_as_admin()
    {
        $admin = $this->createAdminUser();
        $finca = $this->createFinca();
        $this->createInventario(['finca_id' => $finca->id]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/inventarios-bufalo?nopaginate=true');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['id', 'finca_id', 'total_bufalos']]]);
        
        $data = $response->json('data');
        $this->assertFalse(isset($data['current_page']));
    }

    public function test_propietario_can_only_see_their_fincas_inventarios()
    {
        [$user, $propietario] = $this->createPropietarioUser();
        $fincaUser = $this->createFinca(['propietario_id' => $propietario->id]);
        $fincaOther = $this->createFinca();
        
        $this->createInventario(['finca_id' => $fincaUser->id]);
        $this->createInventario(['finca_id' => $fincaOther->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/inventarios-bufalo');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_can_create_inventario_bufalo()
    {
        $admin = $this->createAdminUser();
        $finca = $this->createFinca();

        $data = [
            'finca_id' => $finca->id,
            'num_becerro' => 5,
            'num_anojo' => 3,
            'fecha_inventario' => '2023-10-01'
        ];

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/inventarios-bufalo', $data);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);
                 
        $this->assertDatabaseHas('inventario_bufalos', [
            'finca_id' => $finca->id,
            'num_becerro' => 5,
            'num_anojo' => 3
        ]);
    }

    public function test_cannot_create_without_permission()
    {
        $user = User::factory()->create();
        $role = \App\Models\Role::firstOrCreate(['code' => 'user', 'name' => 'User']);
        $user->roles()->attach($role);
        $finca = $this->createFinca();

        $data = [
            'finca_id' => $finca->id,
            'fecha_inventario' => '2023-10-01'
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/inventarios-bufalo', $data);

        $response->assertStatus(403);
    }

    public function test_can_show_inventario_bufalo()
    {
        $admin = $this->createAdminUser();
        $finca = $this->createFinca();
        $inventario = $this->createInventario(['finca_id' => $finca->id]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/inventarios-bufalo/' . $inventario->id);

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $inventario->id);
    }

    public function test_can_update_inventario_bufalo()
    {
        $admin = $this->createAdminUser();
        $finca = $this->createFinca();
        $inventario = $this->createInventario(['finca_id' => $finca->id, 'num_becerro' => 2]);

        $data = [
            'num_becerro' => 10,
        ];

        $response = $this->actingAs($admin, 'sanctum')->putJson('/api/inventarios-bufalo/' . $inventario->id, $data);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('inventario_bufalos', [
            'id' => $inventario->id,
            'num_becerro' => 10
        ]);
    }

    public function test_can_delete_inventario_bufalo()
    {
        $admin = $this->createAdminUser();
        $finca = $this->createFinca();
        $inventario = $this->createInventario(['finca_id' => $finca->id]);

        $response = $this->actingAs($admin, 'sanctum')->deleteJson('/api/inventarios-bufalo/' . $inventario->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('inventario_bufalos', ['id' => $inventario->id]);
    }

    public function test_cannot_update_inventario_not_found()
    {
        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin, 'sanctum')->putJson('/api/inventarios-bufalo/999999', [
            'num_becerro' => 10,
        ]);

        $response->assertStatus(404);
    }

    public function test_propietario_cannot_update_other_finca()
    {
        [$user, $propietario] = $this->createPropietarioUser();
        $otherFinca = $this->createFinca();
        $inventario = $this->createInventario(['finca_id' => $otherFinca->id]);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/inventarios-bufalo/' . $inventario->id, [
            'num_becerro' => 10,
        ]);

        $response->assertStatus(403);
    }

    public function test_propietario_cannot_show_other_finca()
    {
        [$user, $propietario] = $this->createPropietarioUser();
        $otherFinca = $this->createFinca();
        $inventario = $this->createInventario(['finca_id' => $otherFinca->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/inventarios-bufalo/' . $inventario->id);

        $response->assertStatus(403);
    }

    public function test_propietario_cannot_delete_other_finca()
    {
        [$user, $propietario] = $this->createPropietarioUser();
        $otherFinca = $this->createFinca();
        $inventario = $this->createInventario(['finca_id' => $otherFinca->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/inventarios-bufalo/' . $inventario->id);

        $response->assertStatus(403);
    }

    public function test_store_validation_fails()
    {
        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/inventarios-bufalo', [
            'finca_id' => 99999, // invalid
            'num_becerro' => -1, // invalid
        ]);

        $response->assertStatus(422);
    }

    public function test_update_validation_fails()
    {
        $admin = $this->createAdminUser();
        $finca = $this->createFinca();
        $inventario = $this->createInventario(['finca_id' => $finca->id]);

        $response = $this->actingAs($admin, 'sanctum')->putJson('/api/inventarios-bufalo/' . $inventario->id, [
            'finca_id' => 99999, // invalid
        ]);

        $response->assertStatus(422);
    }
}
