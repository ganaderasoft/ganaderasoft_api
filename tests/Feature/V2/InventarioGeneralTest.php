<?php

namespace Tests\Feature\V2;

use App\Models\Finca;
use App\Models\InventarioGeneral;
use App\Models\User;
use App\Models\Role;
use App\Models\Propietario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Illuminate\Support\Str;

class InventarioGeneralTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function createAdmin()
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin', 'guard_name' => 'api']);
        $admin->roles()->attach($role);
        return $admin;
    }

    private function createFinca()
    {
        $persona = \App\Models\Persona::create([
            'cedula' => 'V' . rand(1000000, 9999999),
            'nombre' => 'Test',
            'apellido' => 'User',
            'correo' => 'test' . rand(1, 9999) . '@example.com',
            'status' => 'activo'
        ]);

        $propietario = Propietario::create(['persona_id' => $persona->id]);
        
        return Finca::create([
            'nombre' => 'Finca Test ' . Str::random(5),
            'propietario_id' => $propietario->id,
            'explotacion_tipo' => 'ganaderia',
            'archivado' => false
        ]);
    }
    
    private function createInventarioGeneral($fincaId, $data = [])
    {
        return InventarioGeneral::create(array_merge([
            'finca_id' => $fincaId,
            'num_personal' => 10,
            'fecha_inventario' => '2023-01-01',
        ], $data));
    }

    private function createUserWithFinca()
    {
        $user = User::factory()->create();
        $roleProp = Role::where('code', 'propietario')->first();
        if ($roleProp) {
            $user->roles()->syncWithoutDetaching([$roleProp->id]);
        }

        $persona = \App\Models\Persona::create([
            'cedula' => 'V' . rand(1000000, 9999999),
            'nombre' => 'Test',
            'apellido' => 'User',
            'correo' => $user->email,
            'status' => 'activo'
        ]);

        $user->personas()->syncWithoutDetaching([$persona->id]);
        $propietario = Propietario::create(['persona_id' => $persona->id]);

        $finca = Finca::create([
            'nombre' => 'Finca Test ' . Str::random(5),
            'propietario_id' => $propietario->id,
            'explotacion_tipo' => 'ganaderia',
            'archivado' => false
        ]);

        $user->fincas()->attach($finca, ['access_level' => 'owner', 'status' => 'active']);
        return [$user, $finca];
    }

    public function test_admin_can_list_all_inventarios_v2()
    {
        $admin = $this->createAdmin();
        $finca = $this->createFinca();
        $this->createInventarioGeneral($finca->id);
        $this->createInventarioGeneral($finca->id);
        $this->createInventarioGeneral($finca->id);

        $response = $this->actingAs($admin)
            ->getJson('/api/inventario-general', ['X-API-VERSION' => '2']);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'data' => [
                             '*' => ['id', 'finca_id', 'num_personal', 'fecha_inventario']
                         ],
                         'current_page',
                         'last_page',
                         'per_page',
                         'total'
                     ]
                 ]);
    }

    public function test_user_can_list_own_inventarios_v2()
    {
        [$user, $finca] = $this->createUserWithFinca();
        $this->createInventarioGeneral($finca->id);
        $this->createInventarioGeneral($finca->id);
        
        $otherFinca = $this->createFinca();
        $this->createInventarioGeneral($otherFinca->id);

        $response = $this->actingAs($user)
            ->getJson('/api/inventario-general', ['X-API-VERSION' => '2']);

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data.data');
    }

    public function test_user_can_list_own_inventarios_nopaginate_v2()
    {
        [$user, $finca] = $this->createUserWithFinca();
        $this->createInventarioGeneral($finca->id);
        $this->createInventarioGeneral($finca->id);

        $response = $this->actingAs($user)
            ->getJson('/api/inventario-general?nopaginate=true', ['X-API-VERSION' => '2']);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => ['id', 'finca_id', 'num_personal', 'fecha_inventario']
                     ]
                 ]);
        
        $this->assertFalse(isset($response->json('data')['current_page']));
        $this->assertCount(2, $response->json('data'));
    }

    public function test_user_can_create_inventario_v2()
    {
        [$user, $finca] = $this->createUserWithFinca();
        $data = [
            'finca_id' => $finca->id,
            'num_personal' => 5,
            'fecha_inventario' => '2023-10-10'
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/inventario-general', $data, ['X-API-VERSION' => '2']);

        $response->assertStatus(201)
                 ->assertJsonFragment(['num_personal' => 5]);
                 
        $this->assertDatabaseHas('inventario_generals', $data);
    }
    
    public function test_user_cannot_create_inventario_for_unauthorized_finca_v2()
    {
        $user = User::factory()->create();
        $otherFinca = $this->createFinca();
        
        $data = [
            'finca_id' => $otherFinca->id,
            'num_personal' => 5,
            'fecha_inventario' => '2023-10-10'
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/inventario-general', $data, ['X-API-VERSION' => '2']);

        $response->assertStatus(403);
    }

    public function test_user_can_show_own_inventario_v2()
    {
        [$user, $finca] = $this->createUserWithFinca();
        $inv = $this->createInventarioGeneral($finca->id);

        $response = $this->actingAs($user)
            ->getJson("/api/inventario-general/{$inv->id}", ['X-API-VERSION' => '2']);

        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $inv->id]);
    }

    public function test_user_cannot_show_unauthorized_inventario_v2()
    {
        $user = User::factory()->create();
        $finca = $this->createFinca();
        $inv = $this->createInventarioGeneral($finca->id);

        $response = $this->actingAs($user)
            ->getJson("/api/inventario-general/{$inv->id}", ['X-API-VERSION' => '2']);

        $response->assertStatus(403);
    }
    
    public function test_returns_404_if_not_found_v2()
    {
        [$user, $finca] = $this->createUserWithFinca();

        $response = $this->actingAs($user)
            ->getJson("/api/inventario-general/999999", ['X-API-VERSION' => '2']);

        $response->assertStatus(404);
    }

    public function test_user_can_update_own_inventario_v2()
    {
        [$user, $finca] = $this->createUserWithFinca();
        $inv = $this->createInventarioGeneral($finca->id);

        $response = $this->actingAs($user)
            ->putJson("/api/inventario-general/{$inv->id}", ['num_personal' => 10], ['X-API-VERSION' => '2']);

        $response->assertStatus(200)
                 ->assertJsonFragment(['num_personal' => 10]);
                 
        $this->assertDatabaseHas('inventario_generals', ['id' => $inv->id, 'num_personal' => 10]);
    }
    
    public function test_user_cannot_update_unauthorized_inventario_v2()
    {
        $user = User::factory()->create();
        $finca = $this->createFinca();
        $inv = $this->createInventarioGeneral($finca->id);

        $response = $this->actingAs($user)
            ->putJson("/api/inventario-general/{$inv->id}", ['num_personal' => 10], ['X-API-VERSION' => '2']);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_inventario_v2()
    {
        [$user, $finca] = $this->createUserWithFinca();
        $inv = $this->createInventarioGeneral($finca->id);

        $response = $this->actingAs($user)
            ->deleteJson("/api/inventario-general/{$inv->id}", [], ['X-API-VERSION' => '2']);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('inventario_generals', ['id' => $inv->id]);
    }
    
    public function test_user_cannot_delete_unauthorized_inventario_v2()
    {
        $user = User::factory()->create();
        $finca = $this->createFinca();
        $inv = $this->createInventarioGeneral($finca->id);

        $response = $this->actingAs($user)
            ->deleteJson("/api/inventario-general/{$inv->id}", [], ['X-API-VERSION' => '2']);

        $response->assertStatus(403);
    }
    
    public function test_admin_can_filter_by_date_v2()
    {
        $admin = $this->createAdmin();
        $finca = $this->createFinca();
        $this->createInventarioGeneral($finca->id, ['fecha_inventario' => '2023-01-10']);
        $this->createInventarioGeneral($finca->id, ['fecha_inventario' => '2023-05-10']);

        $response = $this->actingAs($admin)
            ->getJson('/api/inventario-general?fecha_inicio=2023-02-01&fecha_fin=2023-06-01', ['X-API-VERSION' => '2']);

        $response->assertStatus(200);
    }
    
    public function test_returns_422_on_invalid_data()
    {
        [$user, $finca] = $this->createUserWithFinca();
        
        $response = $this->actingAs($user)
            ->postJson('/api/inventario-general', ['num_personal' => 'invalid'], ['X-API-VERSION' => '2']);
            
        $response->assertStatus(422);
    }
    
    public function test_update_returns_404()
    {
        [$user, $finca] = $this->createUserWithFinca();
        $response = $this->actingAs($user)
            ->putJson("/api/inventario-general/999999", ['num_personal' => 10], ['X-API-VERSION' => '2']);
        $response->assertStatus(404);
    }
    
    public function test_update_returns_422_on_invalid_data()
    {
        [$user, $finca] = $this->createUserWithFinca();
        $inv = $this->createInventarioGeneral($finca->id);
        
        $response = $this->actingAs($user)
            ->putJson("/api/inventario-general/{$inv->id}", ['num_personal' => 'invalid'], ['X-API-VERSION' => '2']);
            
        $response->assertStatus(422);
    }
    
    public function test_delete_returns_404()
    {
        [$user, $finca] = $this->createUserWithFinca();
        $response = $this->actingAs($user)
            ->deleteJson("/api/inventario-general/999999", [], ['X-API-VERSION' => '2']);
        $response->assertStatus(404);
    }
}
