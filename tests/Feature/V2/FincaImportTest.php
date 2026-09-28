<?php

namespace Tests\Feature\V2;

use App\Models\Finca;
use App\Models\Hierro;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Role;
use App\Models\Terreno;
use App\Models\User;
use App\Services\Finca\FincaImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FincaImportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $regularUser;
    protected Propietario $propietario;
    protected FincaImportService $fincaImportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['X-API-VERSION' => '2']);

        $this->fincaImportService = app(FincaImportService::class);

        // 1. Roles y usuarios
        $roleAdmin = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->syncWithoutDetaching([$roleAdmin->id]);

        $this->regularUser = User::factory()->create();

        // 2. Propietario
        $persona = Persona::create([
            'cedula'   => 'V' . rand(10000000, 99999999),
            'nombre'   => 'Carlos',
            'apellido' => 'Mendoza',
            'telefono' => '04121234567',
            'correo'   => rand(1, 99999) . 'cmendoza@test.com',
            'status'   => 'activo',
        ]);
        $this->propietario = Propietario::create(['persona_id' => $persona->id]);
    }

    public function test_import_fincas_with_comma_delimiter_successfully()
    {
        $csvContent = implode("\n", [
            "nombre,explotacion_tipo,identificador_hierro,superficie,relieve,fuente_agua",
            "Finca La Pradera,Mixto,FLP-01,150.5,Plano,Rio",
            "Hacienda El Cedro,Intensiva,HEC-02,80.0,Ondulado,Pozo",
        ]);

        $file = UploadedFile::fake()->createWithContent('fincas_coma.csv', $csvContent);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/fincas/importar', [
                'archivo'        => $file,
                'propietario_id' => $this->propietario->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.total_creadas', 2);

        $this->assertDatabaseHas('fincas', [
            'nombre'           => 'Finca La Pradera',
            'explotacion_tipo' => 'Mixto',
            'propietario_id'   => $this->propietario->id,
        ]);

        $this->assertDatabaseHas('fincas', [
            'nombre'           => 'Hacienda El Cedro',
            'explotacion_tipo' => 'Intensiva',
            'propietario_id'   => $this->propietario->id,
        ]);

        $this->assertDatabaseHas('hierros', [
            'identificador'  => 'FLP-01',
            'propietario_id' => $this->propietario->id,
        ]);

        $this->assertDatabaseHas('terrenos', [
            'superficie'  => 150.5,
            'relieve'     => 'Plano',
            'fuente_agua' => 'Rio',
        ]);
    }

    public function test_import_fincas_with_semicolon_delimiter_and_terreno_hierro()
    {
        $csvContent = implode("\n", [
            "nombre;explotacion_tipo;identificador_hierro;superficie;relieve;fuente_agua;suelo_textura",
            "Finca Los Mangos;Extensiva;FLM-03;300;Plano;Quebrada;Franco",
            "Agropecuaria Central;Mixto;;120.5;Ondulado;Pozo;Arcilloso",
        ]);

        $file = UploadedFile::fake()->createWithContent('fincas_puntoycoma.csv', $csvContent);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/fincas/importar', [
                'archivo'        => $file,
                'propietario_id' => $this->propietario->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.total_creadas', 2);

        $this->assertDatabaseHas('fincas', [
            'nombre'           => 'Finca Los Mangos',
            'explotacion_tipo' => 'Extensiva',
            'propietario_id'   => $this->propietario->id,
        ]);

        $this->assertDatabaseHas('hierros', [
            'identificador' => 'FLM-03',
        ]);

        $this->assertDatabaseHas('terrenos', [
            'superficie'    => 300.0,
            'suelo_textura' => 'Franco',
        ]);
    }

    public function test_import_fincas_headerless_fallback()
    {
        $csvContent = implode("\n", [
            "Finca Sin Encabezado,Mixto,FSE-99,50,Plano,Pozo",
        ]);

        $file = UploadedFile::fake()->createWithContent('fincas_no_header.txt', $csvContent);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/fincas/importar', [
                'archivo'        => $file,
                'propietario_id' => $this->propietario->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.total_creadas', 1);

        $this->assertDatabaseHas('fincas', [
            'nombre'           => 'Finca Sin Encabezado',
            'explotacion_tipo' => 'Mixto',
        ]);
    }

    public function test_import_fincas_validation_errors_rollbacks_transaction()
    {
        // El segundo registro tiene un nombre con más de 25 caracteres (inválido)
        $csvContent = implode("\n", [
            "nombre,explotacion_tipo,identificador_hierro,superficie",
            "Finca Valida Uno,Mixto,FV1,100",
            "Nombre De Finca Demasiado Largo Que Supera Veinticinco Caracteres,Intensiva,F2,50",
        ]);

        $file = UploadedFile::fake()->createWithContent('fincas_invalido.csv', $csvContent);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/fincas/importar', [
                'archivo'        => $file,
                'propietario_id' => $this->propietario->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'import_errors',
                ],
            ]);

        // Asegurar que la primera finca NO se creó (Rollback completo)
        $this->assertDatabaseMissing('fincas', [
            'nombre' => 'Finca Valida Uno',
        ]);
    }

    public function test_import_fincas_without_permission_returns_422_or_403()
    {
        $csvContent = implode("\n", [
            "nombre,explotacion_tipo,identificador_hierro",
            "Finca No Permitida,Mixto,FNP-01",
        ]);

        $file = UploadedFile::fake()->createWithContent('fincas_noperm.csv', $csvContent);

        // Usuario regular sin permisos sobre el propietario
        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->postJson('/api/fincas/importar', [
                'archivo'        => $file,
                'propietario_id' => $this->propietario->id,
            ]);

        // Debe fallar con 422 (errores de validación por fila) o 403
        $this->assertContains($response->status(), [403, 422]);
    }

    public function test_import_fincas_invalid_file_extension_returns_422()
    {
        $file = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/fincas/importar', [
                'archivo'        => $file,
                'propietario_id' => $this->propietario->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['archivo']);
    }

    public function test_download_finca_import_template_successfully()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->get('/api/fincas/importar/plantilla');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('nombre,explotacion_tipo,identificador_hierro', $response->getContent());
        $this->assertStringContainsString('Hacienda Santa Ines', $response->getContent());
    }

    public function test_import_fincas_with_owner_id_in_csv_row()
    {
        $csvContent = implode("\n", [
            "nombre,explotacion_tipo,propietario_id",
            "Finca Con ID Prop,Mixto,{$this->propietario->id}",
        ]);

        $file = UploadedFile::fake()->createWithContent('fincas_con_prop.csv', $csvContent);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/fincas/importar', [
                'archivo' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.total_creadas', 1);

        $this->assertDatabaseHas('fincas', [
            'nombre'         => 'Finca Con ID Prop',
            'propietario_id' => $this->propietario->id,
        ]);
    }
}
