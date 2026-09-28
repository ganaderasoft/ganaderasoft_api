<?php

namespace Tests\Feature\V2;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\PersonalFinca;
use App\Models\Propietario;
use App\Models\Rebano;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_estadisticas_fincas_v2_admin()
    {
        $adminUser = User::factory()->create();
        $adminRole = \App\Models\Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
        $adminUser->roles()->attach($adminRole->id);

        
        $persona = \App\Models\Persona::firstOrCreate(['correo' => 'test@prop.com'], [
            'nombre' => 'Test Prop',
            'apellido' => 'Test',
            'cedula' => 'V123456'
        ]);
        $propietario = Propietario::firstOrCreate(['persona_id' => $persona->id]);

        $finca = Finca::create([
            'propietario_id' => $propietario->id,
            'nombre' => 'Finca V2',
        ]);

        $rebano = Rebano::create([
            'finca_id' => $finca->id,
            'nombre' => 'Rebano V2',
        ]);



        $response = $this->actingAs($adminUser)->getJson("/api/reportes/fincas?propietario_id={$propietario->id}", [
            'X-API-VERSION' => '2'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'resumen',
                'animales_por_sexo',
                'personal_por_tipo',
                'fincas' => [
                    '*' => [
                        'finca_id',
                        'nombre',
                        'cantidad_rebanos',
                        'cantidad_animales',
                        'cantidad_personal',
                    ]
                ],
                'rebanos' => [
                    '*' => [
                        'rebano_id',
                        'finca_id',
                        'nombre',
                        'cantidad_animales',
                    ]
                ]
            ]
        ]);
        
        $response->assertJsonFragment([
            'finca_id' => $finca->id,
            'nombre' => 'Finca V2'
        ]);
    }

    public function test_estadisticas_fincas_v2_propietario()
    {
        $propietarioUser = User::factory()->create();
        $propietarioRole = \App\Models\Role::firstOrCreate(['code' => 'propietario'], ['name' => 'Propietario']);
        $propietarioUser->roles()->attach($propietarioRole->id);
        $persona = \App\Models\Persona::firstOrCreate(['correo' => 'test2@prop.com'], [
            'nombre' => 'Test Prop 2',
            'apellido' => 'Test',
            'cedula' => 'V1234567'
        ]);
        $propietarioUser->personas()->attach($persona->id);
        
        $propietario = Propietario::firstOrCreate(['persona_id' => $persona->id]);

        $finca = Finca::firstOrCreate(['propietario_id' => $propietario->id], [
            'nombre' => 'Finca Prop',
            'archivado' => false,
            'explotacion_tipo' => 'Carne'
        ]);

        $response = $this->actingAs($propietarioUser)->getJson("/api/reportes/fincas", [
            'X-API-VERSION' => '2'
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'finca_id' => $finca->id,
        ]);
    }

    public function test_no_es_propietario_error()
    {
        $user = User::factory()->create();
        $propietarioRole = \App\Models\Role::firstOrCreate(['code' => 'propietario'], ['name' => 'Propietario']);
        $user->roles()->attach($propietarioRole->id);
        // No tiene propietario asignado

        $response = $this->actingAs($user)->getJson("/api/reportes/fincas", [
            'X-API-VERSION' => '2'
        ]);

        $response->assertStatus(403);
    }
    
    public function test_propietario_not_found_admin()
    {
        $adminUser = User::factory()->create();
        $adminRole = \App\Models\Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
        $adminUser->roles()->attach($adminRole->id);

        $response = $this->actingAs($adminUser)->getJson("/api/reportes/fincas?propietario_id=9999", [
            'X-API-VERSION' => '2'
        ]);

        $response->assertStatus(404);
    }
}
