<?php

namespace Tests\Feature\Legacy;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Rebano;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RebanoLegacyTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdmin()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
        $user->roles()->attach($role);
        return $user;
    }

    private function createFinca()
    {
        $persona = Persona::create([
            'cedula' => 'V' . rand(10000000, 99999999),
            'nombre' => 'P',
            'apellido' => 'P',
            'telefono' => '04141234567',
            'correo' => rand(1,1000).'p@p.com',
            'status' => 'activo'
        ]);
        $propietario = Propietario::create(['persona_id' => $persona->id]);

        return Finca::create([
            'propietario_id' => $propietario->id,
            'nombre' => 'Finca ' . rand(1, 100),
            'explotacion_tipo' => 'doble proposito',
            'archivado' => false
        ]);
    }

    private function createRebano($fincaId, $nombre = 'Rebano Test')
    {
        return Rebano::create([
            'finca_id' => $fincaId,
            'nombre' => $nombre,
            'archivado' => false
        ]);
    }

    public function test_admin_can_list_all_rebanos_legacy()
    {
        $admin = $this->createAdmin();
        $finca = $this->createFinca();
        $this->createRebano($finca->id);
        $this->createRebano($finca->id);

        // No X-API-VERSION header
        $response = $this->actingAs($admin)->getJson('/api/rebanos');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['data' => [['id_Rebano', 'id_Finca', 'Nombre', 'archivado']]]]);
    }

    public function test_admin_can_create_rebano_legacy()
    {
        $admin = $this->createAdmin();
        $finca = $this->createFinca();

        $data = [
            'id_Finca' => $finca->id,
            'Nombre' => 'Legacy Rebaño',
        ];

        $response = $this->actingAs($admin)->postJson('/api/rebanos', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.Nombre', 'Legacy Rebaño')
            ->assertJsonPath('data.id_Finca', $finca->id);
    }

    public function test_show_rebano_legacy()
    {
        $admin = $this->createAdmin();
        $finca = $this->createFinca();
        $rebano = $this->createRebano($finca->id);

        $response = $this->actingAs($admin)->getJson("/api/rebanos/{$rebano->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id_Rebano', $rebano->id)
            ->assertJsonStructure(['data' => ['id_Rebano', 'id_Finca', 'Nombre', 'finca']]);
    }

    public function test_update_rebano_legacy()
    {
        $admin = $this->createAdmin();
        $finca = $this->createFinca();
        $rebano = $this->createRebano($finca->id, 'Old Name');

        $response = $this->actingAs($admin)->putJson("/api/rebanos/{$rebano->id}", [
            'Nombre' => 'New Name Legacy'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.Nombre', 'New Name Legacy');
    }

    public function test_can_delete_rebano_with_animals_legacy()
    {
        $admin = $this->createAdmin();
        $finca = $this->createFinca();
        $rebano = $this->createRebano($finca->id);
        
        $razaId = \DB::table('composicion_razas')->value('id') ?? \DB::table('composicion_razas')->insertGetId([
            'nombre' => 'Mestizo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $animal = Animal::create([
            'rebano_id' => $rebano->id,
            'nombre' => 'Animal',
            'codigo_animal' => '123',
            'sexo' => 'M',
            'fecha_nacimiento' => '2020-01-01',
            'composicion_raza_id' => $razaId,
            'archivado' => false
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/rebanos/{$rebano->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('rebanos', ['id' => $rebano->id]);
        $this->assertDatabaseMissing('animals', ['id' => $animal->id]);
    }
}
