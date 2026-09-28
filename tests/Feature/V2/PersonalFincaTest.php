<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use App\Models\User;
use App\Models\Finca;
use App\Models\Propietario;
use App\Models\Persona;
use App\Models\Role;
use App\Models\TipoTrabajador;
use App\Models\PersonalFinca;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PersonalFincaTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $propietarioUser;
    protected $finca;
    protected $tipoTrabajador;
    protected $personalFinca;
    protected $otherPropietarioUser;
    protected $otherFinca;
    protected $otherPersonalFinca;

    protected function setUp(): void
    {
        parent::setUp();
        
        $roleAdmin = Role::firstOrCreate(['code' => 'admin', 'name' => 'Admin']);
        $roleProp = Role::firstOrCreate(['code' => 'propietario', 'name' => 'Propietario']);

        $this->admin = User::firstOrCreate(['email' => 'admin@test.com'], [
            'name' => 'Admin',
            'password' => bcrypt('password')
        ]);
        $this->admin->roles()->syncWithoutDetaching([$roleAdmin->id]);

        $this->propietarioUser = User::firstOrCreate(['email' => 'prop@test.com'], [
            'name' => 'Prop',
            'password' => bcrypt('password')
        ]);
        $this->propietarioUser->roles()->syncWithoutDetaching([$roleProp->id]);

        $personaProp = Persona::firstOrCreate(['cedula' => 'V99999999'], [
            'nombre' => 'Prop',
            'apellido' => 'Test',
            'telefono' => '123456',
            'correo' => 'prop@test.com'
        ]);
        
        $this->propietarioUser->personas()->syncWithoutDetaching([$personaProp->id]);

        $propietario = Propietario::firstOrCreate(['persona_id' => $personaProp->id], []);

        $this->finca = Finca::firstOrCreate(['nombre' => 'Finca Test'], [
            'propietario_id' => $propietario->id
        ]);

        $this->tipoTrabajador = TipoTrabajador::firstOrCreate(['nombre' => 'Administrador']);

        $personaTrabajador = Persona::firstOrCreate(['cedula' => 'V11111111'], [
            'nombre' => 'Trabajador',
            'apellido' => 'Test',
        ]);
        $this->personalFinca = PersonalFinca::firstOrCreate([
            'finca_id' => $this->finca->id,
            'persona_id' => $personaTrabajador->id,
            'tipo_trabajador_id' => $this->tipoTrabajador->id,
        ]);

        // Setup other owner and finca
        $this->otherPropietarioUser = User::firstOrCreate(['email' => 'otherprop@test.com'], [
            'name' => 'OtherProp',
            'password' => bcrypt('password')
        ]);
        $this->otherPropietarioUser->roles()->syncWithoutDetaching([$roleProp->id]);

        $personaPropOther = Persona::firstOrCreate(['cedula' => 'V88888888'], [
            'nombre' => 'OtherProp',
            'apellido' => 'Test',
            'telefono' => '654321',
            'correo' => 'otherprop@test.com'
        ]);
        
        $this->otherPropietarioUser->personas()->syncWithoutDetaching([$personaPropOther->id]);

        $propietarioOther = Propietario::firstOrCreate(['persona_id' => $personaPropOther->id], []);

        $this->otherFinca = Finca::firstOrCreate(['nombre' => 'Other Finca'], [
            'propietario_id' => $propietarioOther->id
        ]);

        $personaTrabajadorOther = Persona::firstOrCreate(['cedula' => 'V22222222'], [
            'nombre' => 'Trabajador2',
            'apellido' => 'Test2',
        ]);
        $this->otherPersonalFinca = PersonalFinca::firstOrCreate([
            'finca_id' => $this->otherFinca->id,
            'persona_id' => $personaTrabajadorOther->id,
            'tipo_trabajador_id' => $this->tipoTrabajador->id,
        ]);
    }

    public function test_can_list_personal_finca()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/personal-finca', [
            'X-API-VERSION' => '2'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success', 'message', 'data' => [
                         'data', 'current_page', 'last_page', 'per_page', 'total'
                     ]
                 ]);
    }

    public function test_can_list_personal_finca_nopaginate()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/personal-finca?nopaginate=true', [
            'X-API-VERSION' => '2'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success', 'message', 'data' => [
                         '*' => [
                             'id',
                             'finca_id',
                             'persona',
                             'tipo_trabajador'
                         ]
                     ]
                 ]);
        
        $this->assertFalse(isset($response->json('data')['current_page']));
    }

    public function test_can_create_personal_finca()
    {
        $payload = [
            'finca_id' => $this->finca->id,
            'cedula' => 'V12345678',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'telefono' => '04141234567',
            'correo' => 'juan@test.com',
            'tipo_trabajador_id' => $this->tipoTrabajador->id,
        ];

        $response = $this->actingAs($this->propietarioUser)->postJson('/api/personal-finca', $payload, [
            'X-API-VERSION' => '2'
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);
    }
    
    public function test_can_show_personal_finca()
    {
        $response = $this->actingAs($this->propietarioUser)->getJson('/api/personal-finca/' . $this->personalFinca->id, [
            'X-API-VERSION' => '2'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }
    
    public function test_can_update_personal_finca()
    {
        $payload = [
            'nombre' => 'Pedro',
        ];

        $response = $this->actingAs($this->propietarioUser)->putJson('/api/personal-finca/' . $this->personalFinca->id, $payload, [
            'X-API-VERSION' => '2'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    public function test_can_delete_personal_finca()
    {
        $response = $this->actingAs($this->admin)->deleteJson('/api/personal-finca/' . $this->personalFinca->id, [], [
            'X-API-VERSION' => '2'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    public function test_non_admin_non_propietario_cannot_list()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/personal-finca', [
            'X-API-VERSION' => '2'
        ]);
        $response->assertStatus(403);
    }

    public function test_propietario_cannot_create_for_other_finca()
    {
        $payload = [
            'finca_id' => $this->otherFinca->id,
            'cedula' => 'V12345679',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'telefono' => '04141234567',
            'correo' => 'juan2@test.com',
            'tipo_trabajador_id' => $this->tipoTrabajador->id,
        ];
        $response = $this->actingAs($this->propietarioUser)->postJson('/api/personal-finca', $payload, [
            'X-API-VERSION' => '2'
        ]);
        $response->assertStatus(403);
    }

    public function test_show_personal_finca_not_found()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/personal-finca/999999', [
            'X-API-VERSION' => '2'
        ]);
        $response->assertStatus(404);
    }

    public function test_propietario_cannot_show_other_finca_personal()
    {
        $response = $this->actingAs($this->propietarioUser)->getJson('/api/personal-finca/' . $this->otherPersonalFinca->id, [
            'X-API-VERSION' => '2'
        ]);
        $response->assertStatus(403);
    }

    public function test_propietario_cannot_update_other_finca_personal()
    {
        $payload = ['nombre' => 'Modificado'];
        $response = $this->actingAs($this->propietarioUser)->putJson('/api/personal-finca/' . $this->otherPersonalFinca->id, $payload, [
            'X-API-VERSION' => '2'
        ]);
        $response->assertStatus(403);
    }

    public function test_propietario_cannot_delete_other_finca_personal()
    {
        $response = $this->actingAs($this->propietarioUser)->deleteJson('/api/personal-finca/' . $this->otherPersonalFinca->id, [], [
            'X-API-VERSION' => '2'
        ]);
        $response->assertStatus(403);
    }
}
