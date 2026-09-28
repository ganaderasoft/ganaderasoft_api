<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\TipoTrabajador;
use App\Models\PersonalFinca;
use App\Models\Finca;
use App\Models\Persona;
use App\Models\Propietario;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TipoTrabajadorTest extends TestCase
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
        return $user;
    }

    private function createPropietario()
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['code' => 'propietario'], ['name' => 'Propietario']);
        $user->roles()->syncWithoutDetaching([$role->id]);

        $permission = Permission::firstOrCreate(
            ['code' => 'tipo_trabajador.read'],
            ['module' => 'admin', 'action' => 'read']
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        return $user;
    }

    private function createUserWithoutPermissions()
    {
        return User::factory()->create();
    }

    public function test_admin_can_list_tipos_trabajador()
    {
        $admin = $this->createAdmin();
        TipoTrabajador::firstOrCreate(['nombre' => 'Veterinario Test']);

        $response = $this->actingAs($admin)->getJson('/api/tipos-trabajador');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Lista de tipos de trabajador obtenida exitosamente'
            ]);
    }

    public function test_propietario_with_read_permission_can_list_tipos_trabajador()
    {
        $propietario = $this->createPropietario();
        TipoTrabajador::firstOrCreate(['nombre' => 'Operario Test']);

        $response = $this->actingAs($propietario)->getJson('/api/tipos-trabajador');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_user_without_permission_cannot_list_tipos_trabajador()
    {
        $user = $this->createUserWithoutPermissions();

        $response = $this->actingAs($user)->getJson('/api/tipos-trabajador');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_tipo_trabajador()
    {
        $admin = $this->createAdmin();
        $payload = ['nombre' => 'Agrónomo Especialista'];

        $response = $this->actingAs($admin)->postJson('/api/tipos-trabajador', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de trabajador creado exitosamente',
                'data' => [
                    'nombre' => 'Agrónomo Especialista'
                ]
            ]);

        $this->assertDatabaseHas('tipo_trabajadors', [
            'nombre' => 'Agrónomo Especialista'
        ]);
    }

    public function test_non_admin_cannot_create_tipo_trabajador()
    {
        $propietario = $this->createPropietario();
        $payload = ['nombre' => 'Nuevo Tipo No Autorizado'];

        $response = $this->actingAs($propietario)->postJson('/api/tipos-trabajador', $payload);

        $response->assertStatus(403);
    }

    public function test_admin_can_show_tipo_trabajador()
    {
        $admin = $this->createAdmin();
        $tipo = TipoTrabajador::firstOrCreate(['nombre' => 'Capataz']);

        $response = $this->actingAs($admin)->getJson("/api/tipos-trabajador/{$tipo->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $tipo->id,
                    'nombre' => 'Capataz'
                ]
            ]);
    }

    public function test_admin_can_update_tipo_trabajador()
    {
        $admin = $this->createAdmin();
        $tipo = TipoTrabajador::create(['nombre' => 'Nombre Viejo']);

        $response = $this->actingAs($admin)->putJson("/api/tipos-trabajador/{$tipo->id}", [
            'nombre' => 'Nombre Actualizado'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $tipo->id,
                    'nombre' => 'Nombre Actualizado'
                ]
            ]);

        $this->assertDatabaseHas('tipo_trabajadors', [
            'id' => $tipo->id,
            'nombre' => 'Nombre Actualizado'
        ]);
    }

    public function test_non_admin_cannot_update_tipo_trabajador()
    {
        $propietario = $this->createPropietario();
        $tipo = TipoTrabajador::firstOrCreate(['nombre' => 'Tipo Intocable']);

        $response = $this->actingAs($propietario)->putJson("/api/tipos-trabajador/{$tipo->id}", [
            'nombre' => 'Cambio Denegado'
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_tipo_trabajador()
    {
        $admin = $this->createAdmin();
        $tipo = TipoTrabajador::create(['nombre' => 'Tipo Para Borrar']);

        $response = $this->actingAs($admin)->deleteJson("/api/tipos-trabajador/{$tipo->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tipo de trabajador eliminado exitosamente'
            ]);

        $this->assertDatabaseMissing('tipo_trabajadors', [
            'id' => $tipo->id
        ]);
    }

    public function test_non_admin_cannot_delete_tipo_trabajador()
    {
        $propietario = $this->createPropietario();
        $tipo = TipoTrabajador::firstOrCreate(['nombre' => 'Tipo Seguro']);

        $response = $this->actingAs($propietario)->deleteJson("/api/tipos-trabajador/{$tipo->id}");

        $response->assertStatus(403);
    }

    public function test_cannot_delete_tipo_trabajador_with_assigned_personal()
    {
        $admin = $this->createAdmin();
        $tipo = TipoTrabajador::create(['nombre' => 'Tipo Con Personal']);

        $personaProp = Persona::create([
            'cedula' => 'V' . rand(10000000, 99999999),
            'nombre' => 'Prop',
            'apellido' => 'Test',
            'telefono' => '123456',
            'correo' => rand(1, 1000) . 'proptest@test.com',
            'status' => 'activo'
        ]);
        $propietario = Propietario::create(['persona_id' => $personaProp->id]);
        $finca = Finca::create([
            'propietario_id' => $propietario->id,
            'nombre' => 'Finca Test Personal',
            'explotacion_tipo' => 'doble proposito',
            'archivado' => false
        ]);

        $personaEmp = Persona::create([
            'cedula' => 'V' . rand(10000000, 99999999),
            'nombre' => 'Empleado',
            'apellido' => 'Test',
            'telefono' => '654321',
            'correo' => rand(1, 1000) . 'emp@test.com',
            'status' => 'activo'
        ]);

        PersonalFinca::create([
            'finca_id' => $finca->id,
            'persona_id' => $personaEmp->id,
            'tipo_trabajador_id' => $tipo->id,
            'status' => true,
            'fecha_ingreso' => now()
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/tipos-trabajador/{$tipo->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'No se puede eliminar el tipo de trabajador porque tiene personal asignado.'
            ]);

        $this->assertDatabaseHas('tipo_trabajadors', [
            'id' => $tipo->id
        ]);
    }
}
