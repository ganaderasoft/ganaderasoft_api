<?php

namespace Tests\Feature\V2;

use App\Models\Finca;
use App\Models\InventarioVacuno;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventarioVacunoTest extends TestCase
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
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => bcrypt('password')]
        );
        $user->roles()->syncWithoutDetaching([$role->id]);
        $user->load('roles');
        return $user;
    }

    private function createPropietarioUser()
    {
        $role = Role::firstOrCreate(['code' => 'propietario'], ['name' => 'Propietario']);
        $user = User::firstOrCreate(
            ['email' => 'propietario@example.com'],
            ['name' => 'Propietario User', 'password' => bcrypt('password')]
        );
        $user->roles()->syncWithoutDetaching([$role->id]);
        $user->load('roles');

        if ($user->personas()->count() === 0) {
            $persona = \App\Models\Persona::create([
                'cedula' => 'V' . rand(1000000, 9999999),
                'nombre' => 'Propietario',
                'apellido' => 'User',
                'correo' => $user->email,
                'status' => 'activo'
            ]);
            $user->personas()->attach($persona->id);
            \App\Models\Propietario::create(['persona_id' => $persona->id]);
        }

        return $user;
    }

    private function getFinca()
    {
        return Finca::firstOrCreate(
            ['nombre' => 'Finca Test'],
            ['propietario_id' => 1]
        );
    }


    public function test_admin_can_list_all_inventarios()
    {
        $admin = $this->createAdminUser();
        $finca = $this->getFinca();
        InventarioVacuno::create(['finca_id' => $finca->id, 'num_vaca' => 5]);
        InventarioVacuno::create(['finca_id' => $finca->id, 'num_vaca' => 3]);

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/inventario-vacuno');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => ['id', 'finca_id', 'num_vaca', 'total']
                    ],
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ],
            ]);
    }

    public function test_admin_can_list_all_inventarios_nopaginate()
    {
        $admin = $this->createAdminUser();
        $finca = $this->getFinca();
        InventarioVacuno::create(['finca_id' => $finca->id, 'num_vaca' => 5]);

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/inventario-vacuno?nopaginate=true');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'finca_id', 'num_vaca', 'total']
                ],
            ]);

        $this->assertFalse(isset($response->json('data')['current_page']));
    }

    public function test_propietario_can_list_own_inventarios()
    {
        $propietario = $this->createPropietarioUser();
        $finca = $this->getFinca();
        if (!$propietario->fincas->contains($finca->id)) {
            $propietario->fincas()->attach($finca->id);
        }

        InventarioVacuno::create(['finca_id' => $finca->id, 'num_vaca' => 10]);

        $otherFinca = Finca::firstOrCreate(
            ['nombre' => 'Other Finca'],
            ['propietario_id' => $finca->propietario_id]
        );
        InventarioVacuno::create(['finca_id' => $otherFinca->id, 'num_vaca' => 2]);

        $response = $this->actingAs($propietario)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/inventario-vacuno');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data['data']);
    }

    public function test_propietario_cannot_list_others_inventarios()
    {
        $propietario = $this->createPropietarioUser();
        $otherFinca = Finca::firstOrCreate(
            ['nombre' => 'Other Finca 2'],
            ['propietario_id' => 1]
        );

        $response = $this->actingAs($propietario)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/inventario-vacuno?finca_id=' . $otherFinca->id);

        $response->assertStatus(403);
    }

    public function test_store_inventario_validation()
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/inventario-vacuno', [
                'finca_id' => 99999,
                'num_vaca' => -5,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['finca_id', 'num_vaca']);
    }

    public function test_propietario_can_store_inventario()
    {
        $propietario = $this->createPropietarioUser();
        $finca = $this->getFinca();
        if (!$propietario->fincas->contains($finca->id)) {
            $propietario->fincas()->attach($finca->id);
        }

        $response = $this->actingAs($propietario)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/inventario-vacuno', [
                'finca_id' => $finca->id,
                'num_vaca' => 10,
                'num_toro' => 2,
                'fecha_inventario' => '2026-07-01'
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.num_vaca', 10)
            ->assertJsonPath('data.total', 12);
    }

    public function test_propietario_can_show_inventario()
    {
        $propietario = $this->createPropietarioUser();
        $finca = $this->getFinca();
        if (!$propietario->fincas->contains($finca->id)) {
            $propietario->fincas()->attach($finca->id);
        }
        $inv = InventarioVacuno::create(['finca_id' => $finca->id, 'num_vaca' => 4]);

        $response = $this->actingAs($propietario)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/inventario-vacuno/' . $inv->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $inv->id);
    }

    public function test_propietario_cannot_show_not_found_inventario()
    {
        $propietario = $this->createPropietarioUser();

        $response = $this->actingAs($propietario)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/inventario-vacuno/99999');

        $response->assertStatus(404);
    }

    public function test_propietario_can_update_inventario()
    {
        $propietario = $this->createPropietarioUser();
        $finca = $this->getFinca();
        if (!$propietario->fincas->contains($finca->id)) {
            $propietario->fincas()->attach($finca->id);
        }
        $inv = InventarioVacuno::create(['finca_id' => $finca->id, 'num_vaca' => 5]);

        $response = $this->actingAs($propietario)
            ->withHeader('X-API-VERSION', '2')
            ->putJson('/api/inventario-vacuno/' . $inv->id, [
                'num_vaca' => 20
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.num_vaca', 20);
    }

    public function test_propietario_can_delete_inventario()
    {
        $propietario = $this->createPropietarioUser();
        $finca = $this->getFinca();
        if (!$propietario->fincas->contains($finca->id)) {
            $propietario->fincas()->attach($finca->id);
        }
        $inv = InventarioVacuno::create(['finca_id' => $finca->id, 'num_vaca' => 6]);

        $response = $this->actingAs($propietario)
            ->withHeader('X-API-VERSION', '2')
            ->deleteJson('/api/inventario-vacuno/' . $inv->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('inventario_vacunos', ['id' => $inv->id]);
    }

    public function test_update_validation_fails()
    {
        $admin = $this->createAdminUser();
        $finca = $this->getFinca();
        $inv = InventarioVacuno::create(['finca_id' => $finca->id, 'num_vaca' => 5]);

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->putJson('/api/inventario-vacuno/' . $inv->id, [
                'num_vaca' => -10
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['num_vaca']);
    }

    public function test_update_not_found()
    {
        $admin = $this->createAdminUser();
        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->putJson('/api/inventario-vacuno/99999', ['num_vaca' => 5]);

        $response->assertStatus(404);
    }

    public function test_delete_not_found()
    {
        $admin = $this->createAdminUser();
        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->deleteJson('/api/inventario-vacuno/99999');

        $response->assertStatus(404);
    }

    public function test_propietario_cannot_store_unauthorized_finca()
    {
        $propietario = $this->createPropietarioUser();
        $otherPersona = \App\Models\Persona::create(['cedula' => 'V77777777', 'nombre' => 'P7', 'apellido' => 'P7', 'status' => 'activo']);
        $otherPropietario = \App\Models\Propietario::create(['persona_id' => $otherPersona->id]);
        $otherFinca = Finca::firstOrCreate(
            ['nombre' => 'Other Finca 3'],
            ['propietario_id' => $otherPropietario->id]
        );

        $response = $this->actingAs($propietario)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/inventario-vacuno', [
                'finca_id' => $otherFinca->id,
                'num_vaca' => 10
            ]);

        $response->assertStatus(403);
    }

    public function test_propietario_cannot_update_unauthorized_finca()
    {
        $propietario = $this->createPropietarioUser();
        $otherPersona = \App\Models\Persona::create(['cedula' => 'V77777776', 'nombre' => 'P8', 'apellido' => 'P8', 'status' => 'activo']);
        $otherPropietario = \App\Models\Propietario::create(['persona_id' => $otherPersona->id]);
        $otherFinca = Finca::firstOrCreate(
            ['nombre' => 'Other Finca 4'],
            ['propietario_id' => $otherPropietario->id]
        );
        $inv = InventarioVacuno::create(['finca_id' => $otherFinca->id, 'num_vaca' => 5]);

        $response = $this->actingAs($propietario)
            ->withHeader('X-API-VERSION', '2')
            ->putJson('/api/inventario-vacuno/' . $inv->id, [
                'num_vaca' => 10
            ]);

        $response->assertStatus(403);
    }

    public function test_propietario_cannot_delete_unauthorized_finca()
    {
        $propietario = $this->createPropietarioUser();
        $otherPersona = \App\Models\Persona::create(['cedula' => 'V77777775', 'nombre' => 'P9', 'apellido' => 'P9', 'status' => 'activo']);
        $otherPropietario = \App\Models\Propietario::create(['persona_id' => $otherPersona->id]);
        $otherFinca = Finca::firstOrCreate(
            ['nombre' => 'Other Finca 5'],
            ['propietario_id' => $otherPropietario->id]
        );
        $inv = InventarioVacuno::create(['finca_id' => $otherFinca->id, 'num_vaca' => 5]);

        $response = $this->actingAs($propietario)
            ->withHeader('X-API-VERSION', '2')
            ->deleteJson('/api/inventario-vacuno/' . $inv->id);

        $response->assertStatus(403);
    }
}
