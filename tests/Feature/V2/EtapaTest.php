<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Etapa;
use App\Models\TipoAnimal;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class EtapaTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['X-API-VERSION' => '2']);
    }

    private function createAdmin()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
        $user->roles()->syncWithoutDetaching([$role->id]);

        $permissions = ['etapa.read', 'etapa.create', 'etapa.update', 'etapa.delete'];
        foreach ($permissions as $code) {
            $perm = Permission::firstOrCreate(['code' => $code], ['module' => 'parametros', 'action' => $code]);
            $role->permissions()->syncWithoutDetaching([$perm->id]);
        }

        return $user;
    }

    public function test_admin_can_list_etapas()
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->getJson('/api/etapas');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_create_etapa_with_null_sexo_cualquiera()
    {
        $admin = $this->createAdmin();
        $tipoAnimal = TipoAnimal::firstOrCreate(['nombre' => 'Vacuno Test']);

        $payload = [
            'nombre' => 'Cria General',
            'edad_ini' => 0,
            'edad_fin' => 180,
            'tipo_animal_id' => $tipoAnimal->id,
            'sexo' => null,
        ];

        $response = $this->actingAs($admin)->postJson('/api/etapas', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Cria General')
            ->assertJsonPath('data.sexo', null);

        $this->assertDatabaseHas('etapas', [
            'nombre' => 'Cria General',
            'sexo' => null,
            'tipo_animal_id' => $tipoAnimal->id,
        ]);
    }

    public function test_admin_can_create_etapa_with_specific_sexo()
    {
        $admin = $this->createAdmin();
        $tipoAnimal = TipoAnimal::firstOrCreate(['nombre' => 'Vacuno Test']);

        $payload = [
            'nombre' => 'Becerro Macho Test',
            'edad_ini' => 0,
            'edad_fin' => 180,
            'tipo_animal_id' => $tipoAnimal->id,
            'sexo' => 'M',
        ];

        $response = $this->actingAs($admin)->postJson('/api/etapas', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sexo', 'M');
    }

    public function test_admin_can_update_etapa_to_null_sexo()
    {
        $admin = $this->createAdmin();
        $tipoAnimal = TipoAnimal::firstOrCreate(['nombre' => 'Vacuno Test']);

        $etapa = Etapa::create([
            'nombre' => 'Novilla Test',
            'edad_ini' => 730,
            'edad_fin' => 913,
            'tipo_animal_id' => $tipoAnimal->id,
            'sexo' => 'H',
        ]);

        $updatePayload = [
            'sexo' => null,
        ];

        $response = $this->actingAs($admin)->putJson("/api/etapas/{$etapa->id}", $updatePayload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sexo', null);
    }
}
