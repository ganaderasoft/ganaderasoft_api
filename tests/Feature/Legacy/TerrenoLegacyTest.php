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

class TerrenoLegacyTest extends TestCase
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

    protected function createPropietarioForUser(User $user): Propietario
    {
        $persona = Persona::create([
            'cedula' => 'V' . fake()->unique()->numberBetween(10000000, 99999999),
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'telefono' => '12345678',
            'correo' => $user->email,
            'status' => 'activo',
        ]);

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

    protected function createTerreno(int $fincaId, array $data = []): Terreno
    {
        return Terreno::create(array_merge([
            'finca_id' => $fincaId,
            'superficie' => 100.5,
            'relieve' => 'plano',
            'suelo_textura' => 'arenoso',
            'ph_suelo' => '6',
        ], $data));
    }

    /**
     * Test list terrenos legacy format
     */
    public function test_admin_can_list_all_terrenos_legacy()
    {
        $admin = $this->createUserWithRole('global_admin');

        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);
        $finca1 = $this->createFinca($prop1->id, 'Finca Pedro');
        $terreno = $this->createTerreno($finca1->id, ['superficie' => 10.5, 'relieve' => 'plano']);

        $response = $this->actingAs($admin)
            // Note: NO X-API-VERSION header
            ->getJson('/api/terrenos');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id_Terreno',
                            'id_Finca',
                            'Superficie',
                            'Relieve',
                            'Suelo_Textura',
                            'ph_Suelo',
                            'finca' => [
                                'id_Finca',
                                'id_Propietario',
                                'Nombre',
                                'Explotacion_Tipo',
                            ]
                        ]
                    ]
                ]
            ]);

        $data = $response->json('data.data');
        $this->assertGreaterThanOrEqual(1, count($data));
        $item = collect($data)->firstWhere('id_Terreno', $terreno->id);
        $this->assertNotNull($item);
        $this->assertEquals(10.5, $item['Superficie']);
        $this->assertEquals('plano', $item['Relieve']);
    }

    /**
     * Test list terrenos with legacy filters
     */
    public function test_admin_can_list_terrenos_with_filters_legacy()
    {
        $admin = $this->createUserWithRole('global_admin');

        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);
        $finca1 = $this->createFinca($prop1->id, 'Finca Pedro');
        $this->createTerreno($finca1->id, ['superficie' => 10.5, 'relieve' => 'plano']);

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);
        $finca2 = $this->createFinca($prop2->id, 'Finca Maria');
        $this->createTerreno($finca2->id, ['superficie' => 20.5, 'relieve' => 'ondulado']);

        $response = $this->actingAs($admin)
            ->getJson('/api/terrenos?id_finca=' . $finca2->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals(20.5, $data[0]['Superficie']);
        $this->assertEquals('ondulado', $data[0]['Relieve']);
    }

    /**
     * Test list terrenos for Propietario legacy
     */
    public function test_propietario_can_only_see_their_own_terrenos_legacy()
    {
        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);
        $finca1 = $this->createFinca($prop1->id, 'Finca Pedro');
        $this->createTerreno($finca1->id, ['superficie' => 15.5]);

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);
        $finca2 = $this->createFinca($prop2->id, 'Finca Maria');
        $this->createTerreno($finca2->id, ['superficie' => 35.5]);

        $response = $this->actingAs($user1)
            ->getJson('/api/terrenos');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals(15.5, $data[0]['Superficie']);
    }

    /**
     * Test user without propietario profile cannot list terrenos legacy
     */
    public function test_user_without_propietario_profile_cannot_list_terrenos_legacy()
    {
        $user = $this->createUserWithRole('propietario'); // No profile

        $response = $this->actingAs($user)
            ->getJson('/api/terrenos');

        $response->assertStatus(403);
    }

    /**
     * Test store terreno by Admin legacy
     */
    public function test_admin_can_create_terreno_legacy()
    {
        $admin = $this->createUserWithRole('global_admin');
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id);

        $payload = [
            'id_Finca' => $finca->id,
            'Superficie' => 120.50,
            'Relieve' => 'plano',
            'Suelo_Textura' => 'arcilloso',
            'ph_Suelo' => '6',
            'Precipitacion' => 1500,
            'Velocidad_Viento' => 12.5,
            'Temp_Anual' => '25',
            'Temp_Min' => '18',
            'Temp_Max' => '32',
            'Radiacion' => 450,
            'Fuente_Agua' => 'rio',
            'Caudal_Disponible' => 50,
            'Riego_Metodo' => 'goteo',
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/terrenos', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.Superficie', 120.5)
            ->assertJsonPath('data.id_Finca', $finca->id);

        $this->assertDatabaseHas('terrenos', ['finca_id' => $finca->id, 'superficie' => 120.5, 'fuente_agua' => 'rio']);
    }

    /**
     * Test store terreno by Propietario legacy
     */
    public function test_propietario_can_create_terreno_for_their_own_finca_legacy()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id);

        $payload = [
            'id_Finca' => $finca->id,
            'Superficie' => 80.5,
            'Relieve' => 'plano',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/terrenos', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.Superficie', 80.5);

        $this->assertDatabaseHas('terrenos', ['finca_id' => $finca->id, 'superficie' => 80.5]);
    }

    /**
     * Test store terreno by Propietario for another's finca fails legacy
     */
    public function test_propietario_cannot_create_terreno_for_others_finca_legacy()
    {
        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);
        $finca2 = $this->createFinca($prop2->id);

        $payload = [
            'id_Finca' => $finca2->id,
            'Superficie' => 80.0,
            'Relieve' => 'plano',
        ];

        $response = $this->actingAs($user1)
            ->postJson('/api/terrenos', $payload);

        $response->assertStatus(403);
    }

    /**
     * Test store validation errors legacy
     */
    public function test_store_validation_errors_legacy()
    {
        $admin = $this->createUserWithRole('global_admin');

        $payload = [
            'id_Finca' => 999999, // Non-existent
            'Superficie' => -10, // Must be >= 0
            'Relieve' => 'relieve_demasiado_largo',
            'ph_Suelo' => 'demasiado_largo_ph',
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/terrenos', $payload);

        // Errors will be returned with V2 keys because validator runs on normalized request keys.
        // Wait, the errors returned by the validator:
        // 'errors' => $validator->errors()
        // These will have the keys 'finca_id', 'superficie', 'relieve', 'ph_suelo'. Let's assert that:
        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['finca_id', 'superficie', 'relieve', 'ph_suelo']);
    }

    /**
     * Test show terreno legacy
     */
    public function test_admin_can_show_any_terreno_legacy()
    {
        $admin = $this->createUserWithRole('global_admin');
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id);
        $terreno = $this->createTerreno($finca->id, ['superficie' => 99.9]);

        $response = $this->actingAs($admin)
            ->getJson("/api/terrenos/{$terreno->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.Superficie', 99.9)
            ->assertJsonPath('data.id_Terreno', $terreno->id);
    }

    public function test_propietario_can_show_own_terreno_legacy()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id);
        $terreno = $this->createTerreno($finca->id, ['superficie' => 99.9]);

        $response = $this->actingAs($user)
            ->getJson("/api/terrenos/{$terreno->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.Superficie', 99.9)
            ->assertJsonPath('data.finca.id_Finca', $finca->id);
    }

    public function test_propietario_cannot_show_others_terreno_legacy()
    {
        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);
        $finca2 = $this->createFinca($prop2->id);
        $terreno2 = $this->createTerreno($finca2->id);

        $response = $this->actingAs($user1)
            ->getJson("/api/terrenos/{$terreno2->id}");

        $response->assertStatus(403);
    }

    /**
     * Test update terreno legacy
     */
    public function test_propietario_can_update_own_terreno_legacy()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id);
        $terreno = $this->createTerreno($finca->id, ['superficie' => 50.0]);

        $payload = [
            'Superficie' => 75.5,
            'Relieve' => 'ondulado',
        ];

        $response = $this->actingAs($user)
            ->putJson("/api/terrenos/{$terreno->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.Superficie', 75.5)
            ->assertJsonPath('data.Relieve', 'ondulado');

        $this->assertDatabaseHas('terrenos', ['id' => $terreno->id, 'superficie' => 75.5, 'relieve' => 'ondulado']);
    }

    public function test_propietario_cannot_update_others_terreno_legacy()
    {
        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);
        $finca2 = $this->createFinca($prop2->id);
        $terreno2 = $this->createTerreno($finca2->id);

        $payload = [
            'Superficie' => 100.0,
        ];

        $response = $this->actingAs($user1)
            ->putJson("/api/terrenos/{$terreno2->id}", $payload);

        $response->assertStatus(403);
    }

    /**
     * Test destroy terreno legacy
     */
    public function test_propietario_can_delete_own_terreno_legacy()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id);
        $terreno = $this->createTerreno($finca->id);

        $response = $this->actingAs($user)
            ->deleteJson("/api/terrenos/{$terreno->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('terrenos', ['id' => $terreno->id]);
    }
}
