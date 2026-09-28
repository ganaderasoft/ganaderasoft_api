<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Role;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Finca;
use App\Models\Terreno;
use Database\Seeders\RoleSeeder;

class TerrenoTest extends TestCase
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
     * Test list terrenos for Admin V2
     */
    public function test_admin_can_list_all_terrenos_v2()
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
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/terrenos');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'finca_id',
                            'superficie',
                            'relieve',
                            'suelo_textura',
                            'finca',
                        ]
                    ]
                ]
            ]);

        $data = $response->json('data.data');
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    public function test_admin_can_list_all_terrenos_nopaginate_v2()
    {
        $admin = $this->createUserWithRole('global_admin');

        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);
        $finca1 = $this->createFinca($prop1->id, 'Finca Pedro');
        $this->createTerreno($finca1->id, ['superficie' => 10.5, 'relieve' => 'plano']);

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/terrenos?nopaginate=true');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'finca_id',
                        'superficie',
                        'relieve',
                        'suelo_textura',
                        'finca',
                    ]
                ]
            ]);

        $data = $response->json('data');
        $this->assertFalse(isset($data['current_page']));
        $this->assertGreaterThanOrEqual(1, count($data));
    }

    /**
     * Test list terrenos with filters for Admin V2
     */
    public function test_admin_can_list_terrenos_with_filters_v2()
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
            ->withHeader('X-API-VERSION', '2')
            ->getJson("/api/terrenos?finca_id={$finca2->id}&relieve=ondulado");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals(20.5, $data[0]['superficie']);
        $this->assertEquals('ondulado', $data[0]['relieve']);
    }

    /**
     * Test list terrenos for Propietario V2
     */
    public function test_propietario_can_only_see_their_own_terrenos_v2()
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
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/terrenos');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals(15.5, $data[0]['superficie']);
    }

    /**
     * Test user without propietario profile cannot list terrenos
     */
    public function test_user_without_propietario_profile_cannot_list_terrenos_v2()
    {
        $user = $this->createUserWithRole('propietario'); // No profile

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/terrenos');

        $response->assertStatus(403);
    }

    /**
     * Test store terreno by Admin V2
     */
    public function test_admin_can_create_terreno_for_any_finca_v2()
    {
        $admin = $this->createUserWithRole('global_admin');
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id);

        $payload = [
            'finca_id' => $finca->id,
            'superficie' => 120.50,
            'relieve' => 'plano',
            'suelo_textura' => 'arcilloso',
            'ph_suelo' => '6',
            'precipitacion' => 1500,
            'velocidad_viento' => 12.5,
            'temp_anual' => '25',
            'temp_min' => '18',
            'temp_max' => '32',
            'radiacion' => 450,
            'fuente_agua' => 'rio',
            'caudal_disponible' => 50,
            'riego_metodo' => 'goteo',
        ];

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/terrenos', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.superficie', 120.5);

        $this->assertDatabaseHas('terrenos', ['finca_id' => $finca->id, 'superficie' => 120.5, 'fuente_agua' => 'rio']);
    }

    /**
     * Test store terreno by Propietario V2
     */
    public function test_propietario_can_create_terreno_for_their_own_finca_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id);

        $payload = [
            'finca_id' => $finca->id,
            'superficie' => 80.5,
            'relieve' => 'plano',
        ];

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/terrenos', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.superficie', 80.5);

        $this->assertDatabaseHas('terrenos', ['finca_id' => $finca->id, 'superficie' => 80.5]);
    }

    /**
     * Test store terreno by Propietario for another's finca fails V2
     */
    public function test_propietario_cannot_create_terreno_for_others_finca_v2()
    {
        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);
        $finca2 = $this->createFinca($prop2->id);

        $payload = [
            'finca_id' => $finca2->id,
            'superficie' => 80.0,
            'relieve' => 'plano',
        ];

        $response = $this->actingAs($user1)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/terrenos', $payload);

        $response->assertStatus(403);
    }

    /**
     * Test store validation errors V2
     */
    public function test_store_validation_errors_v2()
    {
        $admin = $this->createUserWithRole('global_admin');

        $payload = [
            'finca_id' => 999999, // Non-existent
            'superficie' => -10, // Must be >= 0
            'relieve' => 'relieve_demasiado_largo',
            'ph_suelo' => 'demasiado_largo_ph',
        ];

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/terrenos', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['finca_id', 'superficie', 'relieve', 'ph_suelo']);
    }

    /**
     * Test show terreno V2
     */
    public function test_admin_can_show_any_terreno_v2()
    {
        $admin = $this->createUserWithRole('global_admin');
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id);
        $terreno = $this->createTerreno($finca->id, ['superficie' => 99.9]);

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->getJson("/api/terrenos/{$terreno->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.superficie', 99.9);
    }

    public function test_propietario_can_show_own_terreno_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id);
        $terreno = $this->createTerreno($finca->id, ['superficie' => 99.9]);

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->getJson("/api/terrenos/{$terreno->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.superficie', 99.9)
            ->assertJsonPath('data.finca.id', $finca->id);
    }

    public function test_propietario_cannot_show_others_terreno_v2()
    {
        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);
        $finca2 = $this->createFinca($prop2->id);
        $terreno2 = $this->createTerreno($finca2->id);

        $response = $this->actingAs($user1)
            ->withHeader('X-API-VERSION', '2')
            ->getJson("/api/terrenos/{$terreno2->id}");

        $response->assertStatus(403);
    }

    public function test_show_returns_404_if_not_found_v2()
    {
        $admin = $this->createUserWithRole('global_admin');

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/terrenos/999999');

        $response->assertStatus(404);
    }

    /**
     * Test update terreno V2
     */
    public function test_propietario_can_update_own_terreno_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id);
        $terreno = $this->createTerreno($finca->id, ['superficie' => 50.0]);

        $payload = [
            'superficie' => 75.5,
            'relieve' => 'ondulado',
        ];

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->putJson("/api/terrenos/{$terreno->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.superficie', 75.5)
            ->assertJsonPath('data.relieve', 'ondulado');

        $this->assertDatabaseHas('terrenos', ['id' => $terreno->id, 'superficie' => 75.5, 'relieve' => 'ondulado']);
    }

    public function test_propietario_cannot_update_others_terreno_v2()
    {
        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);
        $finca2 = $this->createFinca($prop2->id);
        $terreno2 = $this->createTerreno($finca2->id);

        $payload = [
            'superficie' => 100.0,
        ];

        $response = $this->actingAs($user1)
            ->withHeader('X-API-VERSION', '2')
            ->putJson("/api/terrenos/{$terreno2->id}", $payload);

        $response->assertStatus(403);
    }

    public function test_update_returns_404_if_not_found_v2()
    {
        $admin = $this->createUserWithRole('global_admin');

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->putJson('/api/terrenos/999999', ['superficie' => 10.0]);

        $response->assertStatus(404);
    }

    /**
     * Test destroy terreno physical delete V2
     */
    public function test_propietario_can_delete_own_terreno_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id);
        $terreno = $this->createTerreno($finca->id);

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->deleteJson("/api/terrenos/{$terreno->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('terrenos', ['id' => $terreno->id]);
    }

    public function test_propietario_cannot_delete_others_terreno_v2()
    {
        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);
        $finca2 = $this->createFinca($prop2->id);
        $terreno2 = $this->createTerreno($finca2->id);

        $response = $this->actingAs($user1)
            ->withHeader('X-API-VERSION', '2')
            ->deleteJson("/api/terrenos/{$terreno2->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('terrenos', ['id' => $terreno2->id]);
    }

    public function test_delete_returns_404_if_not_found_v2()
    {
        $admin = $this->createUserWithRole('global_admin');

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->deleteJson('/api/terrenos/999999');

        $response->assertStatus(404);
    }
}
