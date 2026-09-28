<?php

namespace Tests\Feature\V2;

use App\Models\Animal;
use App\Models\ComposicionRaza;
use App\Models\Finca;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Rebano;
use App\Models\Role;
use App\Models\User;
use App\Http\Resources\Animal\AnimalResource;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class AnimalAgeAndArchiveTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $unauthorizedUser;
    protected Finca $finca;
    protected Rebano $rebano;
    protected ComposicionRaza $raza;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['X-API-VERSION' => '2']);

        $roleAdmin = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->syncWithoutDetaching([$roleAdmin->id]);

        $this->unauthorizedUser = User::factory()->create();

        $persona = Persona::create([
            'cedula'   => 'V' . rand(10000000, 99999999),
            'nombre'   => 'Propietario',
            'apellido' => 'Test',
            'telefono' => '04141234567',
            'correo'   => 'prop' . rand(100, 999) . '@test.com',
        ]);

        $propietario = Propietario::create([
            'persona_id' => $persona->id,
            'user_id'    => $this->admin->id,
        ]);

        $this->finca = Finca::create([
            'propietario_id' => $propietario->id,
            'nombre'         => 'Finca Test',
            'hierro'         => 'H' . rand(100, 999),
        ]);

        $this->rebano = Rebano::create([
            'finca_id' => $this->finca->id,
            'nombre'   => 'Rebaño Test',
        ]);

        $this->raza = ComposicionRaza::firstOrCreate(
            ['siglas' => 'HOL'],
            [
                'nombre'    => 'Holstein',
                'proposito' => 'Leche',
                'origen'    => 'Holanda',
            ]
        );
    }

    public function test_animal_accessors_calculate_exact_age_in_days_months_years_and_formatted_text()
    {
        // Animal nacido hace 1 año, 2 meses y 5 días
        $fechaNacimiento = now()->subYears(1)->subMonths(2)->subDays(5)->startOfDay();

        $animal = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'nombre'              => 'Vaca Luna',
            'codigo_animal'       => 'LUNA-' . rand(1000, 9999),
            'sexo'                => 'H',
            'fecha_nacimiento'    => $fechaNacimiento->toDateString(),
            'procedencia'         => 'Nacimiento Local',
            'composicion_raza_id' => $this->raza->id,
            'archivado'           => false,
        ]);

        // 1. Verificación de Accessors del Modelo
        $this->assertGreaterThan(400, $animal->edad_dias);
        $this->assertEquals(14, $animal->edad_meses);
        $this->assertEquals(1, $animal->edad_anos);
        $this->assertStringContainsString('1 año', $animal->edad_formateada);
        $this->assertStringContainsString('2 meses', $animal->edad_formateada);

        // 2. Verificación de Serialización en AnimalResource
        $resource = new AnimalResource($animal);
        $arrayData = $resource->toArray(Request::create('/api/animales/' . $animal->id));

        $this->assertEquals($animal->edad_dias, $arrayData['edad_dias']);
        $this->assertEquals(14, $arrayData['edad_meses']);
        $this->assertEquals(1, $arrayData['edad_anos']);
        $this->assertEquals($animal->edad_formateada, $arrayData['edad_formateada']);
    }

    public function test_young_calf_age_is_formatted_in_days_only()
    {
        $fechaNacimiento = now()->subDays(12)->startOfDay();

        $animal = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'nombre'              => 'Becerro Relámpago',
            'codigo_animal'       => 'BEC-' . rand(1000, 9999),
            'sexo'                => 'M',
            'fecha_nacimiento'    => $fechaNacimiento->toDateString(),
            'procedencia'         => 'Nacimiento Local',
            'composicion_raza_id' => $this->raza->id,
            'archivado'           => false,
        ]);

        $this->assertEquals(12, $animal->edad_dias);
        $this->assertEquals(0, $animal->edad_meses);
        $this->assertEquals(0, $animal->edad_anos);
        $this->assertEquals('12 días', $animal->edad_formateada);
    }

    public function test_list_animals_filters_by_active_by_default()
    {
        $activeAnimal = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'nombre'              => 'Animal Activo',
            'codigo_animal'       => 'ACT-' . rand(1000, 9999),
            'sexo'                => 'M',
            'fecha_nacimiento'    => '2023-01-01',
            'composicion_raza_id' => $this->raza->id,
            'archivado'           => false,
        ]);

        $archivedAnimal = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'nombre'              => 'Animal Archivado',
            'codigo_animal'       => 'ARC-' . rand(1000, 9999),
            'sexo'                => 'H',
            'fecha_nacimiento'    => '2022-01-01',
            'composicion_raza_id' => $this->raza->id,
            'archivado'           => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/animales?rebano_id=' . $this->rebano->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $animalIds = collect($response->json('data.data') ?? $response->json('data'))->pluck('id')->all();
        $this->assertContains($activeAnimal->id, $animalIds);
        $this->assertNotContains($archivedAnimal->id, $animalIds);
    }

    public function test_list_animals_can_filter_archived_only()
    {
        $activeAnimal = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'nombre'              => 'Animal Activo 2',
            'codigo_animal'       => 'ACT2-' . rand(1000, 9999),
            'sexo'                => 'M',
            'fecha_nacimiento'    => '2023-01-01',
            'composicion_raza_id' => $this->raza->id,
            'archivado'           => false,
        ]);

        $archivedAnimal = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'nombre'              => 'Animal Archivado 2',
            'codigo_animal'       => 'ARC2-' . rand(1000, 9999),
            'sexo'                => 'H',
            'fecha_nacimiento'    => '2022-01-01',
            'composicion_raza_id' => $this->raza->id,
            'archivado'           => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/animales?rebano_id=' . $this->rebano->id . '&archivado=true');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $animalIds = collect($response->json('data.data') ?? $response->json('data'))->pluck('id')->all();
        $this->assertContains($archivedAnimal->id, $animalIds);
        $this->assertNotContains($activeAnimal->id, $animalIds);
    }

    public function test_list_animals_can_include_all_animals_with_todos_filter()
    {
        $activeAnimal = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'nombre'              => 'Animal Activo 3',
            'codigo_animal'       => 'ACT3-' . rand(1000, 9999),
            'sexo'                => 'M',
            'fecha_nacimiento'    => '2023-01-01',
            'composicion_raza_id' => $this->raza->id,
            'archivado'           => false,
        ]);

        $archivedAnimal = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'nombre'              => 'Animal Archivado 3',
            'codigo_animal'       => 'ARC3-' . rand(1000, 9999),
            'sexo'                => 'H',
            'fecha_nacimiento'    => '2022-01-01',
            'composicion_raza_id' => $this->raza->id,
            'archivado'           => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/animales?rebano_id=' . $this->rebano->id . '&archivado=todos');

        $response->assertStatus(200);

        $animalIds = collect($response->json('data.data') ?? $response->json('data'))->pluck('id')->all();
        $this->assertContains($activeAnimal->id, $animalIds);
        $this->assertContains($archivedAnimal->id, $animalIds);
    }

    public function test_archive_animal_endpoint_archives_animal()
    {
        $animal = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'nombre'              => 'Animal Para Archivar',
            'codigo_animal'       => 'ARCH-' . rand(1000, 9999),
            'sexo'                => 'H',
            'fecha_nacimiento'    => '2022-05-10',
            'composicion_raza_id' => $this->raza->id,
            'archivado'           => false,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/animales/{$animal->id}/archivar");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Animal archivado exitosamente',
            ])
            ->assertJsonPath('data.archivado', true);

        $this->assertDatabaseHas('animals', [
            'id'        => $animal->id,
            'archivado' => true,
        ]);
    }

    public function test_unarchive_animal_endpoint_reactivates_archived_animal()
    {
        $this->finca->update(['archivado' => true]);
        $this->rebano->update(['archivado' => true]);

        $archivedAnimal = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'nombre'              => 'Animal Para Desarchivar',
            'codigo_animal'       => 'DES-' . rand(1000, 9999),
            'sexo'                => 'H',
            'fecha_nacimiento'    => '2022-05-10',
            'composicion_raza_id' => $this->raza->id,
            'archivado'           => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/animales/{$archivedAnimal->id}/desarchivar");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Animal desarchivado exitosamente',
            ])
            ->assertJsonPath('data.archivado', false);

        $this->assertDatabaseHas('animals', [
            'id'        => $archivedAnimal->id,
            'archivado' => false,
        ]);
        $this->assertDatabaseHas('rebanos', [
            'id'        => $this->rebano->id,
            'archivado' => false,
        ]);
        $this->assertDatabaseHas('fincas', [
            'id'        => $this->finca->id,
            'archivado' => false,
        ]);
    }

    public function test_delete_animal_permanently_deletes_record()
    {
        $animal = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'nombre'              => 'Animal Para Eliminar',
            'codigo_animal'       => 'DEL-' . rand(1000, 9999),
            'sexo'                => 'M',
            'fecha_nacimiento'    => '2022-01-01',
            'composicion_raza_id' => $this->raza->id,
            'archivado'           => false,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/animales/{$animal->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Animal eliminado exitosamente',
            ]);

        $this->assertDatabaseMissing('animals', [
            'id' => $animal->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_unarchive_animal()
    {
        $archivedAnimal = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'nombre'              => 'Animal Unauth',
            'codigo_animal'       => 'UNAUTH-' . rand(1000, 9999),
            'sexo'                => 'M',
            'fecha_nacimiento'    => '2022-01-01',
            'composicion_raza_id' => $this->raza->id,
            'archivado'           => true,
        ]);

        $response = $this->postJson("/api/animales/{$archivedAnimal->id}/desarchivar");
        $response->assertStatus(401);
    }
}
