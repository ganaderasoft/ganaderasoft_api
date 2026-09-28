<?php

namespace Tests\Feature\V2;

use App\Models\Animal;
use App\Models\ComposicionRaza;
use App\Models\EstadoSalud;
use App\Models\Finca;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Rebano;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AnimalImportTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Finca $finca;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['X-API-VERSION' => '2']);

        $this->admin = $this->createAdmin();
        $this->finca = $this->createFinca();
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
        $user->roles()->syncWithoutDetaching([$role->id]);
        return $user;
    }

    private function createFinca(): Finca
    {
        $persona = Persona::create([
            'cedula'   => 'V' . rand(10000000, 99999999),
            'nombre'   => 'Propietario',
            'apellido' => 'Test',
            'telefono' => '04141234567',
            'correo'   => rand(1, 99999) . 'prop@test.com',
            'status'   => 'activo',
        ]);
        $prop = Propietario::create(['persona_id' => $persona->id]);

        return Finca::create([
            'nombre'           => 'Finca Test Import ' . rand(100, 999),
            'ubicacion'        => 'Ubicacion Test',
            'superficie'       => 150.0,
            'explotacion_tipo' => 'Mixto',
            'propietario_id'   => $prop->id,
            'archivado'        => false,
        ]);
    }

    public function test_import_animals_with_comma_delimiter_successfully()
    {
        $csvContent = implode("\n", [
            'codigo_animal,nombre,sexo,fecha_nacimiento,procedencia,rebano,raza,estado_salud,peso',
            'AN-IMP-01,Vaca Estrella,H,2023-01-15,Local,Lote Hembras,Holstein,Sano,380',
            'AN-IMP-02,Toro Campeon,M,2022-05-10,Compra,Lote Machos,Brahman,Sano,520',
        ]);

        $file = UploadedFile::fake()->createWithContent('animales.csv', $csvContent);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/animales/importar', [
            'archivo'  => $file,
            'finca_id' => $this->finca->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_procesados' => 2,
                ]
            ]);

        $this->assertDatabaseHas('animals', [
            'codigo_animal' => 'AN-IMP-01',
            'nombre'        => 'Vaca Estrella',
            'sexo'          => 'H',
        ]);

        $this->assertDatabaseHas('animals', [
            'codigo_animal' => 'AN-IMP-02',
            'nombre'        => 'Toro Campeon',
            'sexo'          => 'M',
        ]);

        $this->assertDatabaseHas('rebanos', [
            'finca_id' => $this->finca->id,
            'nombre'   => 'Lote Hembras',
        ]);

        $this->assertDatabaseHas('rebanos', [
            'finca_id' => $this->finca->id,
            'nombre'   => 'Lote Machos',
        ]);
    }

    public function test_import_animals_with_semicolon_delimiter_successfully()
    {
        $txtContent = implode("\n", [
            'codigo_animal;nombre;sexo;fecha_nacimiento;procedencia;rebano;raza',
            'AN-TXT-01;Becerra Flor;Hembra;15/06/2023;Nacimiento;Rebaño Cria;Shortorn',
            'AN-TXT-02;Becerro Rayo;Macho;20/07/2023;Nacimiento;Rebaño Cria;Shortorn',
        ]);

        $file = UploadedFile::fake()->createWithContent('animales.txt', $txtContent);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/animales/importar', [
            'archivo'  => $file,
            'finca_id' => $this->finca->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_procesados' => 2,
                ]
            ]);

        $this->assertDatabaseHas('animals', [
            'codigo_animal' => 'AN-TXT-01',
            'nombre'        => 'Becerra Flor',
            'sexo'          => 'H',
        ]);

        $this->assertDatabaseHas('animals', [
            'codigo_animal' => 'AN-TXT-02',
            'nombre'        => 'Becerro Rayo',
            'sexo'          => 'M',
        ]);
    }

    public function test_import_fails_when_file_is_missing()
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/animales/importar', [
            'finca_id' => $this->finca->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['archivo']);
    }

    public function test_import_fails_when_finca_does_not_exist()
    {
        $file = UploadedFile::fake()->createWithContent('animales.csv', "codigo,nombre,sexo,fecha_nacimiento\n1,A,M,2023-01-01");

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/animales/importar', [
            'archivo'  => $file,
            'finca_id' => 9999999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['finca_id']);
    }

    public function test_import_detects_duplicate_codigo_animal_within_file()
    {
        $csvContent = implode("\n", [
            'codigo_animal,nombre,sexo,fecha_nacimiento',
            'DUP-001,Animal Uno,M,2023-01-01',
            'DUP-001,Animal Dos,H,2023-01-02',
        ]);

        $file = UploadedFile::fake()->createWithContent('animales.csv', $csvContent);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/animales/importar', [
            'archivo'  => $file,
            'finca_id' => $this->finca->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'success' => false,
            ]);

        $this->assertDatabaseMissing('animals', ['codigo_animal' => 'DUP-001']);
    }

    public function test_import_detects_duplicate_codigo_animal_in_database()
    {
        $rebano = Rebano::create([
            'finca_id'  => $this->finca->id,
            'nombre'    => 'Rebaño Base',
            'archivado' => false,
        ]);

        Animal::create([
            'rebano_id'           => $rebano->id,
            'nombre'              => 'Animal Existente',
            'codigo_animal'       => 'EXIST-99',
            'sexo'                => 'M',
            'fecha_nacimiento'    => '2022-01-01',
            'composicion_raza_id' => 1,
            'archivado'           => false,
        ]);

        $csvContent = implode("\n", [
            'codigo_animal,nombre,sexo,fecha_nacimiento',
            'EXIST-99,Nuevo Animal,H,2023-01-01',
        ]);

        $file = UploadedFile::fake()->createWithContent('animales.csv', $csvContent);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/animales/importar', [
            'archivo'  => $file,
            'finca_id' => $this->finca->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'success' => false,
            ]);
    }

    public function test_import_validates_invalid_sexo_and_rolls_back()
    {
        $csvContent = implode("\n", [
            'codigo_animal,nombre,sexo,fecha_nacimiento',
            'OK-01,Animal Valido,M,2023-01-01',
            'BAD-02,Animal Invalido,DESCONOCIDO,2023-01-01',
        ]);

        $file = UploadedFile::fake()->createWithContent('animales.csv', $csvContent);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/animales/importar', [
            'archivo'  => $file,
            'finca_id' => $this->finca->id,
        ]);

        $response->assertStatus(422);

        // Verifica transaccionalidad: el animal válido tampoco debe ser insertado si hay un error en el lote
        $this->assertDatabaseMissing('animals', ['codigo_animal' => 'OK-01']);
        $this->assertDatabaseMissing('animals', ['codigo_animal' => 'BAD-02']);
    }
}
