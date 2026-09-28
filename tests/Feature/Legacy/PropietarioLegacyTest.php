<?php

namespace Tests\Feature\Legacy;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Role;
use App\Models\Persona;
use App\Models\Propietario;
use Database\Seeders\RoleSeeder;

class PropietarioLegacyTest extends TestCase
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
     * Test list propietarios V1 (index)
     * Expected output: flattened structure with capitalized/legacy field names.
     */
    public function test_admin_can_list_all_propietarios_legacy()
    {
        $admin = $this->createUserWithRole('global_admin');

        $user1 = $this->createUserWithRole('propietario');
        $this->createPropietarioForUser($user1, [
            'nombre' => 'Mateo',
            'apellido' => 'Gomez',
            'telefono' => '9999',
            'cedula' => 'V15000001',
        ]);

        $response = $this->actingAs($admin)
            // Note: NO X-API-VERSION header
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
                            'Nombre',
                            'Apellido',
                            'Telefono',
                            'id_Personal',
                            'archivado',
                            'user',
                            'fincas'
                        ]
                    ]
                ]
            ]);

        // Check formatting details of the items
        $data = $response->json('data.data');
        $item = collect($data)->firstWhere('Nombre', 'Mateo');
        $this->assertNotNull($item);
        $this->assertEquals($user1->id, $item['id']); // legacy id is the user_id
        $this->assertEquals(15000001, $item['id_Personal']); // integer cast
        $this->assertFalse($item['archivado']);
    }

    /**
     * Test create propietario V1 (store)
     * Payload: V1 format (id, Nombre, Apellido, Telefono, id_Personal)
     */
    public function test_admin_can_create_propietario_legacy()
    {
        $admin = $this->createUserWithRole('global_admin');
        $targetUser = $this->createUserWithRole('propietario');

        $payload = [
            'id' => $targetUser->id, // user_id
            'Nombre' => 'Diana',
            'Apellido' => 'Rojas',
            'Telefono' => '555555',
            'id_Personal' => 9991234 // Numeric value -> translates to V9991234
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/propietarios', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.Nombre', 'Diana')
            ->assertJsonPath('data.Apellido', 'Rojas')
            ->assertJsonPath('data.id_Personal', 9991234)
            ->assertJsonPath('data.id', $targetUser->id);

        // Verify DB got the clean V2 values
        $this->assertDatabaseHas('personas', [
            'cedula' => 'V9991234',
            'nombre' => 'Diana',
            'apellido' => 'Rojas',
            'telefono' => '555555',
        ]);
    }

    /**
     * Test show propietario V1
     */
    public function test_admin_can_show_propietario_legacy()
    {
        $admin = $this->createUserWithRole('global_admin');
        $user = $this->createUserWithRole('propietario');
        $propietario = $this->createPropietarioForUser($user, [
            'nombre' => 'Santiago',
            'cedula' => 'V16000002',
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/propietarios/{$propietario->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.Nombre', 'Santiago')
            ->assertJsonPath('data.id_Personal', 16000002);
    }

    /**
     * Test update propietario V1
     */
    public function test_propietario_can_update_own_record_legacy()
    {
        $user = $this->createUserWithRole('propietario');
        $propietario = $this->createPropietarioForUser($user, [
            'nombre' => 'Santiago',
            'telefono' => '111',
            'cedula' => 'V16000002',
        ]);

        $payload = [
            'Nombre' => 'Santiago Modificado',
            'Telefono' => '222',
        ];

        $response = $this->actingAs($user)
            ->putJson("/api/propietarios/{$propietario->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.Nombre', 'Santiago Modificado')
            ->assertJsonPath('data.Telefono', '222');

        $this->assertDatabaseHas('personas', [
            'id' => $propietario->persona_id,
            'nombre' => 'Santiago Modificado',
            'telefono' => '222',
        ]);
    }

    /**
     * Test destroy propietario V1
     */
    public function test_admin_can_delete_propietario_legacy()
    {
        $admin = $this->createUserWithRole('global_admin');
        $user = $this->createUserWithRole('propietario');
        $propietario = $this->createPropietarioForUser($user);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/propietarios/{$propietario->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Propietario eliminado exitosamente');

        $this->assertDatabaseHas('personas', [
            'id' => $propietario->persona_id,
            'status' => 'inactivo',
        ]);
    }

    /**
     * Test create propietario V1 with alphanumeric cedula (already has prefix)
     */
    public function test_create_propietario_legacy_with_alphanumeric_cedula()
    {
        $admin = $this->createUserWithRole('global_admin');
        $targetUser = $this->createUserWithRole('propietario');

        $payload = [
            'id' => $targetUser->id,
            'Nombre' => 'Diana',
            'Apellido' => 'Rojas',
            'Telefono' => '555555',
            'id_Personal' => 'E9991234' // Alphanumeric string -> does not prepend 'V'
        ];

        $response = $this->actingAs($admin)
            ->postJson('/api/propietarios', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id_Personal', 9991234);

        $this->assertDatabaseHas('personas', [
            'cedula' => 'E9991234',
        ]);
    }

    /**
     * Test update propietario legacy with all fields
     */
    public function test_update_propietario_legacy_all_fields()
    {
        $user = $this->createUserWithRole('propietario');
        $propietario = $this->createPropietarioForUser($user, [
            'nombre' => 'Santiago',
            'apellido' => 'Old',
            'telefono' => '111',
            'cedula' => 'V16000002',
        ]);

        $payload = [
            'Nombre' => 'Santiago Modificado',
            'Apellido' => 'Rojas Modificado',
            'Telefono' => '222',
            'id_Personal' => 16000009, // Update id_personal
        ];

        $response = $this->actingAs($user)
            ->putJson("/api/propietarios/{$propietario->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.Nombre', 'Santiago Modificado')
            ->assertJsonPath('data.Apellido', 'Rojas Modificado')
            ->assertJsonPath('data.Telefono', '222')
            ->assertJsonPath('data.id_Personal', 16000009);

        $this->assertDatabaseHas('personas', [
            'id' => $propietario->persona_id,
            'nombre' => 'Santiago Modificado',
            'apellido' => 'Rojas Modificado',
            'telefono' => '222',
            'cedula' => 'V16000009',
        ]);
    }
}
