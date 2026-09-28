<?php

namespace Tests\Feature\Legacy;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\PersonalFinca;
use App\Models\Propietario;
use App\Models\Rebano;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportesLegacyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp(); $this->withoutExceptionHandling();
    }

    public function test_estadisticas_fincas_legacy()
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
            'nombre' => 'Finca Legacy',
        ]);

        $rebano = Rebano::create([
            'finca_id' => $finca->id,
            'nombre' => 'Rebano Legacy',
        ]);

        $response = $this->actingAs($adminUser)->getJson("/api/reportes/fincas?id_propietario={$propietario->id}"); // sin header V2

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
                        'id_Finca',
                        'Nombre',
                        'cantidad_rebanos',
                        'cantidad_animales',
                        'cantidad_personal',
                    ]
                ],
                'rebanos' => [
                    '*' => [
                        'id_Rebano',
                        'id_Finca',
                        'Nombre',
                        'cantidad_animales',
                    ]
                ]
            ]
        ]);

        $response->assertJsonFragment([
            'id_Finca' => $finca->id,
            'Nombre' => 'Finca Legacy'
        ]);
        
        $response->assertJsonFragment([
            'id_Rebano' => $rebano->id,
            'id_Finca' => $finca->id,
            'Nombre' => 'Rebano Legacy'
        ]);
    }
}
