<?php

namespace Tests\Feature\Legacy;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Role;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Finca;
use App\Models\Terreno;
use Database\Seeders\RoleSeeder;

class FincaLegacyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (Role::count() === 0) {
            $this->seed(RoleSeeder::class);
        }
    }

    protected function createUserWithRole(string $roleCode): User
    {
        $user = User::factory()->create();
        $role = Role::where('code', $roleCode)->first();
        if ($role) {
            $user->roles()->attach($role->id);
        }
        return $user;
    }

    protected function createPropietarioForUser(User $user, array $personaData = []): Propietario
    {
        $persona = Persona::create(array_merge([
            'cedula' => 'V' . fake()->unique()->numberBetween(10000000, 99999999),
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'telefono' => '12345678',
            'correo' => $user->email,
            'status' => 'activo',
        ], $personaData));

        $user->personas()->attach($persona->id);

        return Propietario::create([
            'persona_id' => $persona->id,
        ]);
    }

    protected function createFinca(int $propietarioId, string $nombre = 'Finca Test'): Finca
    {
        return Finca::create([
            'propietario_id' => $propietarioId,
            'nombre' => $nombre,
            'explotacion_tipo' => 'ganado',
            'archivado' => false,
        ]);
    }

    /**
     * Test list fincas legacy
     */
    public function test_admin_can_list_all_fincas_legacy()
    {
        $admin = $this->createUserWithRole('global_admin');

        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1, [
            'nombre' => 'Mateo',
            'cedula' => 'V15000001',
        ]);
        $this->createFinca($prop1->id, 'Finca Mateo');

        $response = $this->actingAs($admin)
            // Note: NO X-API-VERSION header
            ->getJson('/api/fincas');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id_Finca',
                            'id_Propietario',
                            'Nombre',
                            'Explotacion_Tipo',
                            'archivado',
                            'propietario' => [
                                'id',
                                'id_Personal',
                                'Nombre',
                                'Apellido',
                                'Telefono',
                                'archivado',
                                'user',
                            ]
                        ]
                    ]
                ]
            ]);

        $data = $response->json('data.data');
        $item = collect($data)->firstWhere('Nombre', 'Finca Mateo');
        $this->assertNotNull($item);
        $this->assertEquals($prop1->id, $item['id_Propietario']);
        $this->assertEquals('Mateo', $item['propietario']['Nombre']);
        $this->assertEquals(15000001, $item['propietario']['id_Personal']);
    }

    /**
     * Test store finca legacy
     */
    public function test_admin_can_create_finca_legacy()
    {
        $admin = $this->createUserWithRole('global_admin');
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);

        $payload = [
            'id_Propietario' => $prop->id,
            'Nombre' => 'Finca Legacy',
            'Explotacion_Tipo' => 'doble proposito',
            'terreno' => [
                'Superficie' => 85.50,
                'Relieve' => 'ondulado',
                'Suelo_Textura' => 'limoso',
                'ph_Suelo' => '7',
            ]
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/fincas', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.Nombre', 'Finca Legacy')
            ->assertJsonPath('data.terreno.Superficie', 85.5);

        // Verify DB got the clean V2 values
        $this->assertDatabaseHas('fincas', [
            'nombre' => 'Finca Legacy',
            'propietario_id' => $prop->id,
        ]);
        $this->assertDatabaseHas('terrenos', [
            'superficie' => 85.5,
            'relieve' => 'ondulado',
        ]);
    }

    /**
     * Test show finca legacy
     */
    public function test_propietario_can_show_own_finca_legacy()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user, ['nombre' => 'Santiago']);
        $finca = $this->createFinca($prop->id, 'Finca Santiago');

        $response = $this->actingAs($user)
            ->getJson("/api/fincas/{$finca->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id_Finca', $finca->id)
            ->assertJsonPath('data.Nombre', 'Finca Santiago')
            ->assertJsonPath('data.propietario.Nombre', 'Santiago');
    }

    /**
     * Test update finca legacy
     */
    public function test_propietario_can_update_own_finca_legacy()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id, 'Finca Santiago');

        $payload = [
            'Nombre' => 'Finca Santiago Modificada',
            'terreno' => [
                'Superficie' => 120,
            ]
        ];

        $response = $this->actingAs($user)
            ->putJson("/api/fincas/{$finca->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.Nombre', 'Finca Santiago Modificada')
            ->assertJsonPath('data.terreno.Superficie', 120);

        $this->assertDatabaseHas('fincas', [
            'id' => $finca->id,
            'nombre' => 'Finca Santiago Modificada',
        ]);
        $this->assertDatabaseHas('terrenos', [
            'finca_id' => $finca->id,
            'superficie' => 120,
        ]);
    }

    /**
     * Test delete finca legacy
     */
    public function test_propietario_can_delete_own_finca_legacy()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id, 'Finca Santiago');

        $response = $this->actingAs($user)
            ->deleteJson("/api/fincas/{$finca->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('fincas', [
            'id' => $finca->id,
            'archivado' => true,
        ]);
    }
}
