<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Role;
use App\Models\Persona;
use App\Models\Propietario;
use Database\Seeders\RoleSeeder;

class PropietarioTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles if empty
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

    /**
     * Test list propietarios for Admin (access to all)
     */
    public function test_admin_can_list_all_propietarios_v2()
    {
        $admin = $this->createUserWithRole('global_admin');
        
        $user1 = $this->createUserWithRole('propietario');
        $this->createPropietarioForUser($user1, ['nombre' => 'Pedro']);

        $user2 = $this->createUserWithRole('propietario');
        $this->createPropietarioForUser($user2, ['nombre' => 'Maria']);

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/propietarios');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'persona' => [
                                'id',
                                'cedula',
                                'nombre',
                                'apellido',
                                'telefono',
                                'correo',
                                'status'
                            ]
                        ]
                    ]
                ]
            ]);
    }

    /**
     * Test list propietarios for a propietario (only gets own profile)
     */
    public function test_propietario_can_only_see_their_own_record_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $propietario = $this->createPropietarioForUser($user, ['nombre' => 'Roberto']);

        // Create another owner
        $otherUser = $this->createUserWithRole('propietario');
        $this->createPropietarioForUser($otherUser, ['nombre' => 'Otro']);

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/propietarios');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
            
        $data = $response->json('data.data');
        
        // Assert only one result and it's their own
        $this->assertCount(1, $data);
        $this->assertEquals($propietario->id, $data[0]['id']);
        $this->assertEquals('Roberto', $data[0]['persona']['nombre']);
    }

    /**
     * Test store propietario by Admin
     */
    public function test_admin_can_create_propietario_for_any_user_v2()
    {
        $admin = $this->createUserWithRole('global_admin');
        $targetUser = $this->createUserWithRole('propietario');

        $payload = [
            'user_id' => $targetUser->id,
            'cedula' => 'V88888888',
            'nombre' => 'Clara',
            'apellido' => 'Mendez',
            'telefono' => '987654321',
            'correo' => 'clara@example.com',
        ];

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/propietarios', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.persona.nombre', 'Clara')
            ->assertJsonPath('data.persona.cedula', 'V88888888');

        $this->assertDatabaseHas('personas', ['cedula' => 'V88888888']);
        $this->assertDatabaseHas('propietarios', ['persona_id' => Persona::where('cedula', 'V88888888')->first()->id]);
    }

    /**
     * Test store propietario validation fails on invalid cedula format
     */
    public function test_store_propietario_validation_fails_on_invalid_cedula_v2()
    {
        $admin = $this->createUserWithRole('global_admin');
        $targetUser = $this->createUserWithRole('propietario');

        $payload = [
            'user_id' => $targetUser->id,
            'cedula' => '12345678', // Missing letter prefix
            'nombre' => 'Clara',
            'apellido' => 'Mendez',
        ];

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/propietarios', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['cedula']);
    }

    /**
     * Test show propietario
     */
    public function test_admin_can_show_any_propietario_v2()
    {
        $admin = $this->createUserWithRole('global_admin');
        $user = $this->createUserWithRole('propietario');
        $propietario = $this->createPropietarioForUser($user, ['nombre' => 'Lucas']);

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->getJson("/api/propietarios/{$propietario->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.persona.nombre', 'Lucas');
    }

    public function test_propietario_cannot_show_others_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $this->createPropietarioForUser($user, ['nombre' => 'Lucas']);

        $otherUser = $this->createUserWithRole('propietario');
        $otherPropietario = $this->createPropietarioForUser($otherUser, ['nombre' => 'Marcos']);

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->getJson("/api/propietarios/{$otherPropietario->id}");

        $response->assertStatus(403);
    }

    /**
     * Test update propietario
     */
    public function test_propietario_can_update_own_record_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $propietario = $this->createPropietarioForUser($user, ['nombre' => 'Lucas', 'telefono' => '1111']);

        $payload = [
            'nombre' => 'Lucas Modificado',
            'telefono' => '2222',
        ];

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->putJson("/api/propietarios/{$propietario->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.persona.nombre', 'Lucas Modificado')
            ->assertJsonPath('data.persona.telefono', '2222');

        $this->assertDatabaseHas('personas', [
            'id' => $propietario->persona_id,
            'nombre' => 'Lucas Modificado',
            'telefono' => '2222'
        ]);
    }

    /**
     * Test destroy propietario (soft delete)
     */
    public function test_admin_can_delete_propietario_v2()
    {
        $admin = $this->createUserWithRole('global_admin');
        $user = $this->createUserWithRole('propietario');
        $propietario = $this->createPropietarioForUser($user);

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->deleteJson("/api/propietarios/{$propietario->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify status in DB became 'inactivo'
        $this->assertDatabaseHas('personas', [
            'id' => $propietario->persona_id,
            'status' => 'inactivo',
        ]);
    }

    /**
     * Test list propietarios fails if non-admin has no propietario profile
     */
    public function test_non_admin_without_propietario_profile_cannot_list_propietarios_v2()
    {
        $user = $this->createUserWithRole('propietario'); // No propietario profile created

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/propietarios');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /**
     * Test non-admin cannot create propietario for another user
     */
    public function test_non_admin_cannot_create_propietario_for_others_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $otherUser = $this->createUserWithRole('propietario');

        $payload = [
            'user_id' => $otherUser->id,
            'cedula' => 'V88888889',
            'nombre' => 'Test',
            'apellido' => 'Test',
        ];

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/propietarios', $payload);

        $response->assertStatus(403);
    }

    /**
     * Test create fails if propietario profile already exists for user
     */
    public function test_create_propietario_fails_if_already_exists_v2()
    {
        $admin = $this->createUserWithRole('global_admin');
        $targetUser = $this->createUserWithRole('propietario');
        $this->createPropietarioForUser($targetUser);

        $payload = [
            'user_id' => $targetUser->id,
            'cedula' => 'V88888889',
            'nombre' => 'Test',
            'apellido' => 'Test',
        ];

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/propietarios', $payload);

        $response->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    /**
     * Test non-admin cannot delete propietario
     */
    public function test_propietario_cannot_delete_propietario_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $propietario = $this->createPropietarioForUser($user);

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->deleteJson("/api/propietarios/{$propietario->id}");

        $response->assertStatus(403);
    }

    /**
     * Test show returns 404 if not found
     */
    public function test_show_returns_404_if_not_found_v2()
    {
        $admin = $this->createUserWithRole('global_admin');

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/propietarios/999999');

        $response->assertStatus(404);
    }

    /**
     * Test update returns 404 if not found
     */
    public function test_update_returns_404_if_not_found_v2()
    {
        $admin = $this->createUserWithRole('global_admin');

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->putJson('/api/propietarios/999999', ['nombre' => 'Test']);

        $response->assertStatus(404);
    }

    /**
     * Test delete returns 404 if not found
     */
    public function test_delete_returns_404_if_not_found_v2()
    {
        $admin = $this->createUserWithRole('global_admin');

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->deleteJson('/api/propietarios/999999');

        $response->assertStatus(404);
    }
}
