<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Role;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Finca;
use App\Models\Rebano;
use App\Models\Animal;
use App\Models\Terreno;
use Database\Seeders\RoleSeeder;

class FincaTest extends TestCase
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

    /**
     * Test list fincas for Admin
     */
    public function test_admin_can_list_all_fincas_v2()
    {
        $admin = $this->createUserWithRole('global_admin');

        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);
        $this->createFinca($prop1->id, 'Finca Pedro');

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);
        $this->createFinca($prop2->id, 'Finca Maria');

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/fincas');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'nombre',
                            'explotacion_tipo',
                            'archivado',
                            'propietario',
                        ]
                    ]
                ]
            ]);

        $data = $response->json('data.data');
        $this->assertGreaterThanOrEqual(2, count($data));
        $nombres = collect($data)->pluck('nombre')->toArray();
        $this->assertContains('Finca Pedro', $nombres);
        $this->assertContains('Finca Maria', $nombres);
    }

    public function test_admin_can_list_all_fincas_nopaginate_v2()
    {
        $admin = $this->createUserWithRole('global_admin');

        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);
        $this->createFinca($prop1->id, 'Finca Pedro Nopag');

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/fincas?nopaginate=true');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'nombre',
                        'explotacion_tipo',
                        'archivado',
                        'propietario',
                    ]
                ]
            ]);

        $data = $response->json('data');
        $this->assertFalse(isset($data['current_page']));
        $nombres = collect($data)->pluck('nombre')->toArray();
        $this->assertContains('Finca Pedro Nopag', $nombres);
    }

    /**
     * Test list fincas for Propietario
     */
    public function test_propietario_can_only_see_their_own_fincas_v2()
    {
        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);
        $this->createFinca($prop1->id, 'Finca Pedro');

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);
        $this->createFinca($prop2->id, 'Finca Maria');

        $response = $this->actingAs($user1)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/fincas');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('Finca Pedro', $data[0]['nombre']);
    }

    /**
     * Test user without permission cannot list fincas
     */
    public function test_user_without_permission_cannot_list_fincas_v2()
    {
        $user = User::factory()->create(); // No role or permissions

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/fincas');

        $response->assertStatus(403);
    }

    /**
     * Test propietario without fincas gets empty list (200 OK)
     */
    public function test_propietario_without_fincas_gets_empty_list_v2()
    {
        $user = $this->createUserWithRole('propietario'); // Has permission, but 0 fincas

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/fincas');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data.data');
        $this->assertCount(0, $data);
    }

    /**
     * Test store finca by Admin
     */
    public function test_admin_can_create_finca_for_any_propietario_v2()
    {
        $admin = $this->createUserWithRole('global_admin');
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);

        $payload = [
            'propietario_id' => $prop->id,
            'nombre' => 'Finca Nueva',
            'explotacion_tipo' => 'lechera',
            'terreno' => [
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
            ]
        ];

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/fincas', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Finca Nueva')
            ->assertJsonPath('data.terreno.superficie', 120.5);

        $this->assertDatabaseHas('fincas', ['nombre' => 'Finca Nueva']);
        $this->assertDatabaseHas('terrenos', ['superficie' => 120.5, 'fuente_agua' => 'rio']);
    }

    /**
     * Test store finca by Propietario for themselves
     */
    public function test_propietario_can_create_finca_for_themselves_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);

        $payload = [
            'propietario_id' => $prop->id,
            'nombre' => 'Finca Propia',
            'explotacion_tipo' => 'carne',
        ];

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/fincas', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Finca Propia');

        $this->assertDatabaseHas('fincas', ['nombre' => 'Finca Propia', 'propietario_id' => $prop->id]);
    }

    /**
     * Test store finca by Propietario for another propietario fails
     */
    public function test_propietario_cannot_create_finca_for_others_v2()
    {
        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);

        $payload = [
            'propietario_id' => $prop2->id,
            'nombre' => 'Finca Intento',
            'explotacion_tipo' => 'carne',
        ];

        $response = $this->actingAs($user1)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/fincas', $payload);

        $response->assertStatus(403);
    }

    /**
     * Test store validation errors
     */
    public function test_store_validation_errors_v2()
    {
        $admin = $this->createUserWithRole('global_admin');

        $payload = [
            'nombre' => '', // Required
            'explotacion_tipo' => 'explotacion_tipo_demasiado_largo_para_el_campo_limite_veinte_caracteres',
            'propietario_id' => 999999, // Non-existent
        ];

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->postJson('/api/fincas', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['nombre', 'explotacion_tipo', 'propietario_id']);
    }

    /**
     * Test show finca
     */
    public function test_admin_can_show_any_finca_v2()
    {
        $admin = $this->createUserWithRole('global_admin');
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id, 'Finca Show');

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->getJson("/api/fincas/{$finca->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Finca Show');
    }

    public function test_propietario_can_show_own_finca_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id, 'Finca Propia');

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->getJson("/api/fincas/{$finca->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Finca Propia');
    }

    public function test_propietario_cannot_show_others_finca_v2()
    {
        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);
        $finca = $this->createFinca($prop2->id, 'Finca Maria');

        $response = $this->actingAs($user1)
            ->withHeader('X-API-VERSION', '2')
            ->getJson("/api/fincas/{$finca->id}");

        $response->assertStatus(403);
    }

    public function test_show_returns_404_if_not_found_v2()
    {
        $admin = $this->createUserWithRole('global_admin');

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->getJson('/api/fincas/999999');

        $response->assertStatus(404);
    }

    /**
     * Test update finca
     */
    public function test_propietario_can_update_own_finca_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id, 'Finca Vieja');

        $payload = [
            'nombre' => 'Finca Nueva Update',
            'terreno' => [
                'superficie' => 500,
                'relieve' => 'ondulado',
            ]
        ];

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->putJson("/api/fincas/{$finca->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Finca Nueva Update')
            ->assertJsonPath('data.terreno.superficie', 500);

        $this->assertDatabaseHas('fincas', ['id' => $finca->id, 'nombre' => 'Finca Nueva Update']);
        $this->assertDatabaseHas('terrenos', ['finca_id' => $finca->id, 'superficie' => 500]);
    }

    public function test_propietario_cannot_change_finca_ownership_v2()
    {
        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);
        $finca = $this->createFinca($prop1->id, 'Finca F');

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);

        $payload = [
            'propietario_id' => $prop2->id,
        ];

        $response = $this->actingAs($user1)
            ->withHeader('X-API-VERSION', '2')
            ->putJson("/api/fincas/{$finca->id}", $payload);

        $response->assertStatus(403);
    }

    public function test_propietario_cannot_update_others_finca_v2()
    {
        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);
        $finca = $this->createFinca($prop2->id, 'Finca F');

        $payload = [
            'nombre' => 'Intento Hack',
        ];

        $response = $this->actingAs($user1)
            ->withHeader('X-API-VERSION', '2')
            ->putJson("/api/fincas/{$finca->id}", $payload);

        $response->assertStatus(403);
    }

    public function test_update_returns_404_if_not_found_v2()
    {
        $admin = $this->createUserWithRole('global_admin');

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->putJson('/api/fincas/999999', ['nombre' => 'Test']);

        $response->assertStatus(404);
    }

    /**
     * Test list fincas filtering by archivado
     */
    public function test_admin_can_filter_fincas_by_archivado_v2()
    {
        $admin = $this->createUserWithRole('global_admin');
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);

        $fincaActiva = $this->createFinca($prop->id, 'Finca Activa');
        $fincaArchivada = Finca::create([
            'propietario_id' => $prop->id,
            'nombre' => 'Finca Archivada',
            'explotacion_tipo' => 'ganado',
            'archivado' => true,
        ]);

        // 1. Por defecto solo activas
        $respDefault = $this->actingAs($admin)->withHeader('X-API-VERSION', '2')->getJson('/api/fincas');
        $idsDefault = collect($respDefault->json('data.data'))->pluck('id')->toArray();
        $this->assertContains($fincaActiva->id, $idsDefault);
        $this->assertNotContains($fincaArchivada->id, $idsDefault);

        // 2. Solo archivadas
        $respArch = $this->actingAs($admin)->withHeader('X-API-VERSION', '2')->getJson('/api/fincas?archivado=true');
        $idsArch = collect($respArch->json('data.data'))->pluck('id')->toArray();
        $this->assertNotContains($fincaActiva->id, $idsArch);
        $this->assertContains($fincaArchivada->id, $idsArch);

        // 3. Todas (todos)
        $respTodos = $this->actingAs($admin)->withHeader('X-API-VERSION', '2')->getJson('/api/fincas?archivado=todos');
        $idsTodos = collect($respTodos->json('data.data'))->pluck('id')->toArray();
        $this->assertContains($fincaActiva->id, $idsTodos);
        $this->assertContains($fincaArchivada->id, $idsTodos);
    }

    /**
     * Test archivar finca con cascada a rebaños y animales
     */
    public function test_propietario_can_archive_own_finca_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id, 'Finca Archivar');
        $rebano = Rebano::create(['finca_id' => $finca->id, 'nombre' => 'Rebano Test', 'archivado' => false]);
        $razaId = \DB::table('composicion_razas')->value('id') ?? \DB::table('composicion_razas')->insertGetId([
            'nombre' => 'Mestizo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $animal = Animal::create([
            'rebano_id' => $rebano->id,
            'nombre' => 'Vaca Test',
            'codigo_animal' => 'VAC-ARC-01',
            'sexo' => 'H',
            'fecha_nacimiento' => '2023-01-01',
            'composicion_raza_id' => $razaId,
            'procedencia' => 'Nacida en finca',
            'archivado' => false,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->postJson("/api/fincas/{$finca->id}/archivar");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.archivado', true);

        $this->assertDatabaseHas('fincas', [
            'id' => $finca->id,
            'archivado' => true,
        ]);
        $this->assertDatabaseHas('rebanos', [
            'id' => $rebano->id,
            'archivado' => true,
        ]);
        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'archivado' => true,
        ]);
    }

    /**
     * Test desarchivar finca reactiva la finca sin alterar rebaños/animales previamente archivados
     */
    public function test_propietario_can_unarchive_own_finca_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = Finca::create([
            'propietario_id' => $prop->id,
            'nombre' => 'Finca Archivada Prev',
            'explotacion_tipo' => 'ganado',
            'archivado' => true,
        ]);
        $rebano = Rebano::create(['finca_id' => $finca->id, 'nombre' => 'Rebano Test', 'archivado' => true]);
        $razaId = \DB::table('composicion_razas')->value('id') ?? \DB::table('composicion_razas')->insertGetId([
            'nombre' => 'Mestizo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $animal = Animal::create([
            'rebano_id' => $rebano->id,
            'nombre' => 'Vaca Test',
            'codigo_animal' => 'VAC-UNARC-01',
            'sexo' => 'H',
            'fecha_nacimiento' => '2023-01-01',
            'composicion_raza_id' => $razaId,
            'procedencia' => 'Nacida en finca',
            'archivado' => true,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->postJson("/api/fincas/{$finca->id}/desarchivar");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.archivado', false);

        $this->assertDatabaseHas('fincas', [
            'id' => $finca->id,
            'archivado' => false,
        ]);
        // Rebaño y animal permanecen archivados para no resucitar descartes previos
        $this->assertDatabaseHas('rebanos', [
            'id' => $rebano->id,
            'archivado' => true,
        ]);
        $this->assertDatabaseHas('animals', [
            'id' => $animal->id,
            'archivado' => true,
        ]);
    }

    /**
     * Test destroy finca (hard delete)
     */
    public function test_propietario_can_delete_own_empty_finca_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id, 'Finca Borrar Definitivo');

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->deleteJson("/api/fincas/{$finca->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('fincas', [
            'id' => $finca->id,
        ]);
    }

    public function test_propietario_can_delete_finca_with_animals_cascade_v2()
    {
        $user = $this->createUserWithRole('propietario');
        $prop = $this->createPropietarioForUser($user);
        $finca = $this->createFinca($prop->id, 'Finca Con Animales');
        $rebano = \App\Models\Rebano::create(['finca_id' => $finca->id, 'nombre' => 'Rebano Finca']);
        
        $razaId = \DB::table('composicion_razas')->value('id') ?? \DB::table('composicion_razas')->insertGetId([
            'nombre' => 'Mestizo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $animal = \App\Models\Animal::create([
            'rebano_id' => $rebano->id,
            'nombre' => 'Animal Finca',
            'codigo_animal' => 'FNC-123',
            'sexo' => 'M',
            'fecha_nacimiento' => '2022-01-01',
            'composicion_raza_id' => $razaId,
            'archivado' => false
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-API-VERSION', '2')
            ->deleteJson("/api/fincas/{$finca->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('fincas', ['id' => $finca->id]);
        $this->assertDatabaseMissing('rebanos', ['id' => $rebano->id]);
        $this->assertDatabaseMissing('animals', ['id' => $animal->id]);
    }

    public function test_propietario_cannot_archive_others_finca_v2()
    {
        $user1 = $this->createUserWithRole('propietario');
        $prop1 = $this->createPropietarioForUser($user1);

        $user2 = $this->createUserWithRole('propietario');
        $prop2 = $this->createPropietarioForUser($user2);
        $finca = $this->createFinca($prop2->id, 'Finca Ajena');

        $response = $this->actingAs($user1)
            ->withHeader('X-API-VERSION', '2')
            ->postJson("/api/fincas/{$finca->id}/archivar");

        $response->assertStatus(403);
    }

    public function test_delete_returns_404_if_not_found_v2()
    {
        $admin = $this->createUserWithRole('global_admin');

        $response = $this->actingAs($admin)
            ->withHeader('X-API-VERSION', '2')
            ->deleteJson('/api/fincas/999999');

        $response->assertStatus(404);
    }
}
