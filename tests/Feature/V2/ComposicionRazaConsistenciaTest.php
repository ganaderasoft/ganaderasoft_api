<?php

namespace Tests\Feature\V2;

use App\Models\Animal;
use App\Models\ComposicionRaza;
use App\Models\Finca;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Rebano;
use App\Models\Role;
use App\Models\TipoAnimal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ComposicionRazaConsistenciaTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['X-API-VERSION' => '2']);
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
        $user->roles()->attach($role);
        return $user;
    }

    private function createFinca(string $nombre = 'Finca Test'): Finca
    {
        $persona = Persona::create([
            'cedula' => 'V' . rand(10000000, 99999999),
            'nombre' => 'Owner',
            'apellido' => 'Test',
            'telefono' => '0414' . rand(1000000, 9999999),
            'correo' => rand(1, 99999) . 'test@test.com',
            'status' => 'activo'
        ]);
        $propietario = Propietario::create(['persona_id' => $persona->id]);

        return Finca::create([
            'propietario_id' => $propietario->id,
            'nombre' => $nombre,
            'explotacion_tipo' => 'doble proposito',
            'archivado' => false
        ]);
    }

    private function createRebano(int $fincaId, string $nombre = 'Rebaño Test'): Rebano
    {
        return Rebano::create([
            'finca_id' => $fincaId,
            'nombre' => $nombre,
            'archivado' => false
        ]);
    }

    private function createAnimal(int $rebanoId, int $razaId): Animal
    {
        return Animal::create([
            'rebano_id' => $rebanoId,
            'composicion_raza_id' => $razaId,
            'nombre' => 'Animal Test ' . rand(1, 99999),
            'codigo_animal' => 'COD-' . rand(1000, 99999),
            'sexo' => 'H',
            'fecha_nacimiento' => '2023-01-01',
            'procedencia' => 'Nacimiento',
            'archivado' => false
        ]);
    }

    private function createRaza(?int $fincaId = null, string $nombre = 'Raza Test'): ComposicionRaza
    {
        $tipoAnimal = TipoAnimal::firstOrCreate(['nombre' => 'Vacuno']);

        return ComposicionRaza::create([
            'nombre' => $nombre . ' ' . rand(1000, 9999),
            'siglas' => 'RZ' . rand(10, 99),
            'pelaje' => 'Negro',
            'proposito' => 'Carne',
            'tipo_raza' => 'Pura',
            'finca_id' => $fincaId,
            'tipo_animal_id' => $tipoAnimal->id,
        ]);
    }

    public function test_admin_cannot_assign_finca_to_public_breed_used_by_multiple_fincas()
    {
        $admin = $this->createAdmin();
        $finca1 = $this->createFinca('Finca Los Andes');
        $finca2 = $this->createFinca('Finca El Paraíso');

        $rebano1 = $this->createRebano($finca1->id);
        $rebano2 = $this->createRebano($finca2->id);

        $razaPublica = $this->createRaza(null, 'Carora Global');

        // Asociar animales de ambas fincas a la raza pública
        $this->createAnimal($rebano1->id, $razaPublica->id);
        $this->createAnimal($rebano2->id, $razaPublica->id);

        // Intentar asignar Finca 1 a la raza
        $response = $this->actingAs($admin)->putJson("/api/composicion-raza/{$razaPublica->id}", [
            'nombre' => $razaPublica->nombre,
            'finca_id' => $finca1->id,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsString('Finca El Paraíso', $response->json('message'));
        $this->assertStringContainsString('Solo puede asignarse a una finca si es la única que la utiliza', $response->json('message'));

        // Verificar en la BD que sigue siendo pública
        $this->assertNull($razaPublica->fresh()->finca_id);
    }

    public function test_admin_can_assign_finca_if_that_finca_is_the_only_one_using_it()
    {
        $admin = $this->createAdmin();
        $finca1 = $this->createFinca('Finca Exclusiva');
        $rebano1 = $this->createRebano($finca1->id);

        $razaPublica = $this->createRaza(null, 'Raza Solo Finca 1');

        // Solo Finca 1 tiene animales con esta raza
        $this->createAnimal($rebano1->id, $razaPublica->id);

        // Asignar Finca 1 a la raza
        $response = $this->actingAs($admin)->putJson("/api/composicion-raza/{$razaPublica->id}", [
            'nombre' => $razaPublica->nombre,
            'finca_id' => $finca1->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertEquals($finca1->id, $razaPublica->fresh()->finca_id);
    }

    public function test_admin_can_assign_finca_if_no_animals_use_the_breed()
    {
        $admin = $this->createAdmin();
        $finca1 = $this->createFinca('Finca Sin Animales');

        $razaPublica = $this->createRaza(null, 'Raza Sin Uso');

        // No hay animales asociados a esta raza
        $response = $this->actingAs($admin)->putJson("/api/composicion-raza/{$razaPublica->id}", [
            'nombre' => $razaPublica->nombre,
            'finca_id' => $finca1->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertEquals($finca1->id, $razaPublica->fresh()->finca_id);
    }

    public function test_admin_cannot_reassign_breed_to_another_finca_if_used_by_current_finca()
    {
        $admin = $this->createAdmin();
        $finca1 = $this->createFinca('Finca Origen');
        $finca2 = $this->createFinca('Finca Destino');

        $rebano1 = $this->createRebano($finca1->id);

        $razaPrivada = $this->createRaza($finca1->id, 'Raza Privada Finca 1');
        $this->createAnimal($rebano1->id, $razaPrivada->id);

        // Intentar cambiarla a Finca 2
        $response = $this->actingAs($admin)->putJson("/api/composicion-raza/{$razaPrivada->id}", [
            'nombre' => $razaPrivada->nombre,
            'finca_id' => $finca2->id,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsString('Finca Origen', $response->json('message'));
        $this->assertEquals($finca1->id, $razaPrivada->fresh()->finca_id);
    }

    public function test_admin_can_update_other_attributes_without_changing_finca()
    {
        $admin = $this->createAdmin();
        $finca1 = $this->createFinca('Finca Modificar');
        $rebano1 = $this->createRebano($finca1->id);

        $raza = $this->createRaza($finca1->id, 'Raza Original');
        $this->createAnimal($rebano1->id, $raza->id);

        // Editar nombre y mantener finca_id igual
        $response = $this->actingAs($admin)->putJson("/api/composicion-raza/{$raza->id}", [
            'nombre' => 'Raza Nombre Modificado',
            'finca_id' => $finca1->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertEquals('Raza Nombre Modificado', $raza->fresh()->nombre);
    }

    public function test_admin_can_make_private_breed_public_even_if_used()
    {
        $admin = $this->createAdmin();
        $finca1 = $this->createFinca('Finca Liberar');
        $rebano1 = $this->createRebano($finca1->id);

        $raza = $this->createRaza($finca1->id, 'Raza Para Liberar');
        $this->createAnimal($rebano1->id, $raza->id);

        // Convertir a pública (finca_id = null)
        $response = $this->actingAs($admin)->putJson("/api/composicion-raza/{$raza->id}", [
            'nombre' => $raza->nombre,
            'finca_id' => null,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNull($raza->fresh()->finca_id);
    }
}
