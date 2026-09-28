<?php

namespace Tests\Feature\Legacy;

use Tests\TestCase;
use App\Models\User;
use App\Models\Finca;
use App\Models\Propietario;
use App\Models\Persona;
use App\Models\Role;
use App\Models\TipoTrabajador;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PersonalFincaLegacyTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $finca;
    protected $tipoTrabajador;

    protected function setUp(): void
    {
        parent::setUp();
        
        $roleAdmin = Role::firstOrCreate(['code' => 'admin', 'name' => 'Admin']);

        $this->admin = User::firstOrCreate(['email' => 'admin_legacy@test.com'], [
            'name' => 'Admin Legacy',
            'password' => bcrypt('password')
        ]);
        $this->admin->roles()->syncWithoutDetaching([$roleAdmin->id]);

        $personaProp = Persona::firstOrCreate(['cedula' => 'V88888888'], [
            'nombre' => 'Prop Legacy',
            'apellido' => 'Test',
            'telefono' => '123456',
            'correo' => 'proplegacy@test.com'
        ]);

        $propietario = Propietario::firstOrCreate(['persona_id' => $personaProp->id], []);

        $this->finca = Finca::firstOrCreate(['nombre' => 'Finca Legacy Test'], [
            'propietario_id' => $propietario->id
        ]);

        $this->tipoTrabajador = TipoTrabajador::firstOrCreate(['nombre' => 'Administrador']);
    }

    public function test_legacy_can_create_personal_finca()
    {
        $payload = [
            'id_Finca' => $this->finca->id,
            'Cedula' => 98765432,
            'Nombre' => 'Maria',
            'Apellido' => 'Gomez',
            'Telefono' => '04149876543',
            'Correo' => 'maria@test.com',
            'Tipo_Trabajador' => 'Administrador',
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/personal-finca', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success', 'message', 'data' => [
                         'id_Tecnico', 'id_Finca', 'Cedula', 'Nombre', 'Apellido', 'Telefono', 'Correo', 'Tipo_Trabajador'
                     ]
                 ]);
    }
    
    public function test_legacy_can_list_personal_finca()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/personal-finca');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success', 'message', 'data', 'pagination'
                 ]);
    }
    public function test_legacy_can_show_personal_finca()
    {
        $personaTrabajador = Persona::firstOrCreate(['cedula' => 'V11111111'], [
            'nombre' => 'Trabajador',
            'apellido' => 'Test',
        ]);
        $personalFinca = \App\Models\PersonalFinca::firstOrCreate([
            'finca_id' => $this->finca->id,
            'persona_id' => $personaTrabajador->id,
            'tipo_trabajador_id' => $this->tipoTrabajador->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/personal-finca/' . $personalFinca->id);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success', 'message', 'data' => [
                         'id_Tecnico', 'id_Finca', 'Cedula', 'Nombre'
                     ]
                 ]);
    }
    
    public function test_legacy_can_update_personal_finca()
    {
        $personaTrabajador = Persona::firstOrCreate(['cedula' => 'V22222222'], [
            'nombre' => 'Trabajador2',
            'apellido' => 'Test2',
        ]);
        $personalFinca = \App\Models\PersonalFinca::firstOrCreate([
            'finca_id' => $this->finca->id,
            'persona_id' => $personaTrabajador->id,
            'tipo_trabajador_id' => $this->tipoTrabajador->id,
        ]);

        $payload = [
            'Nombre' => 'Nuevo Nombre',
        ];

        $response = $this->actingAs($this->admin)->putJson('/api/personal-finca/' . $personalFinca->id, $payload);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success', 'message', 'data' => [
                         'id_Tecnico', 'id_Finca', 'Cedula', 'Nombre'
                     ]
                 ]);
    }
    
    public function test_legacy_can_delete_personal_finca()
    {
        $personaTrabajador = Persona::firstOrCreate(['cedula' => 'V33333333'], [
            'nombre' => 'Trabajador3',
            'apellido' => 'Test3',
        ]);
        $personalFinca = \App\Models\PersonalFinca::firstOrCreate([
            'finca_id' => $this->finca->id,
            'persona_id' => $personaTrabajador->id,
            'tipo_trabajador_id' => $this->tipoTrabajador->id,
        ]);

        $response = $this->actingAs($this->admin)->deleteJson('/api/personal-finca/' . $personalFinca->id);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }
}
