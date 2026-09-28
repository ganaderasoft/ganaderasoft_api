<?php

namespace Tests\Feature\Legacy;

use App\Models\Finca;
use App\Models\InventarioGeneral;
use App\Models\User;
use App\Models\Role;
use App\Models\Propietario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Illuminate\Support\Str;

class InventarioGeneralLegacyTest extends TestCase
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

    public function test_admin_can_list_all_inventarios_legacy()
    {
        $admin = $this->createAdmin();
        $finca = $this->createFinca();
        $this->createInventarioGeneral($finca->id);
        $this->createInventarioGeneral($finca->id);

        $response = $this->actingAs($admin)
            ->getJson('/api/inventario-general');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['id_InvGen', 'id_Finca', 'Num_Personal', 'Fecha_Inventario']]]);
    }

    public function test_user_can_create_inventario_legacy()
    {
        [$user, $finca] = $this->createUserWithFinca();
        $data = [
            'id_Finca' => $finca->id,
            'Num_Personal' => 5,
            'Fecha_Inventario' => '2023-10-10'
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/inventario-general', $data);

        $response->assertStatus(201)
                 ->assertJsonFragment(['Num_Personal' => 5, 'id_Finca' => $finca->id]);
                 
        $this->assertDatabaseHas('inventario_generals', [
            'finca_id' => $finca->id,
            'num_personal' => 5,
            'fecha_inventario' => '2023-10-10'
        ]);
    }

    public function test_user_can_show_own_inventario_legacy()
    {
        [$user, $finca] = $this->createUserWithFinca();
        $inv = $this->createInventarioGeneral($finca->id);

        $response = $this->actingAs($user)
            ->getJson("/api/inventario-general/{$inv->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['id_InvGen' => $inv->id]);
    }

    public function test_user_can_update_own_inventario_legacy()
    {
        [$user, $finca] = $this->createUserWithFinca();
        $inv = $this->createInventarioGeneral($finca->id);

        $response = $this->actingAs($user)
            ->putJson("/api/inventario-general/{$inv->id}", ['Num_Personal' => 10]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['Num_Personal' => 10]);
                 
        $this->assertDatabaseHas('inventario_generals', ['id' => $inv->id, 'num_personal' => 10]);
    }
}
