<?php

namespace Tests\Feature\V2;

use App\Models\Animal;
use App\Models\ComposicionRaza;
use App\Models\Etapa;
use App\Models\EtapaAnimal;
use App\Models\Finca;
use App\Models\MedidasCorporales;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Rebano;
use App\Models\Role;
use App\Models\User;
use App\Services\Animal\ZoometriaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class IndicesCorporalesTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $regularUser;
    protected Finca $finca;
    protected Rebano $rebano;
    protected Animal $animal;
    protected EtapaAnimal $etapaAnimal;
    protected MedidasCorporales $medida;
    protected ZoometriaService $zoometriaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->zoometriaService = app(ZoometriaService::class);

        // 1. Roles y permisos
        $roleAdmin = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
        $this->admin = User::factory()->create();
        $this->admin->roles()->syncWithoutDetaching([$roleAdmin->id]);

        $this->regularUser = User::factory()->create();

        // 2. Finca y Rebaño
        $persona = Persona::create([
            'cedula'   => 'V' . rand(10000000, 99999999),
            'nombre'   => 'Propietario',
            'apellido' => 'Test',
            'telefono' => '04141234567',
            'correo'   => rand(1, 99999) . 'prop@test.com',
            'status'   => 'activo',
        ]);
        $prop = Propietario::create(['persona_id' => $persona->id]);

        $this->finca = Finca::create([
            'nombre'           => 'Hacienda Zoometría Test',
            'ubicacion'        => 'Sector El Valle',
            'superficie'       => 250.0,
            'explotacion_tipo' => 'Mixto',
            'propietario_id'   => $prop->id,
            'archivado'        => false,
        ]);

        $this->rebano = Rebano::create([
            'finca_id'  => $this->finca->id,
            'nombre'    => 'Lote Zoometria',
            'archivado' => false,
        ]);

        // 3. Animal
        $raza = ComposicionRaza::first() ?? ComposicionRaza::create(['nombre' => 'Holstein', 'siglas' => 'HOL']);
        $this->animal = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'codigo_animal'       => 'ZOO-001',
            'nombre'              => 'Vaca Baronesa',
            'sexo'                => 'H',
            'fecha_nacimiento'    => '2023-01-15',
            'procedencia'         => 'Local',
            'composicion_raza_id' => $raza->id,
            'archivado'           => false,
        ]);

        // 4. Etapa Animal
        $etapa = Etapa::first() ?? Etapa::create(['nombre' => 'Vaca Adulta']);
        $this->etapaAnimal = EtapaAnimal::create([
            'animal_id' => $this->animal->id,
            'etapa_id'  => $etapa->id,
            'fecha_ini' => '2023-01-15',
            'fecha_fin' => null,
        ]);

        // 5. Medidas Corporales completas
        $this->medida = MedidasCorporales::create([
            'animal_etapa_id' => $this->etapaAnimal->id,
            'altura_hc'       => 130.0, // HC = 130 cm
            'altura_hg'       => 135.0, // HG = 135 cm
            'perimetro_pt'    => 180.0, // PT = 180 cm
            'perimetro_pca'   => 20.0,  // PCA = 20 cm
            'longitud_lc'     => 150.0, // LC = 150 cm
            'longitud_lg'     => 45.0,  // LG = 45 cm
            'anchura_ag'      => 50.0,  // AG = 50 cm
        ]);
    }

    /**
     * Prueba el cálculo matemático exacto de las 7 fórmulas zoométricas.
     */
    public function test_calcular_indices_mathematical_formulas_correctly()
    {
        $calculo = $this->zoometriaService->calcularIndices($this->medida);

        $this->assertArrayHasKey('indices', $calculo);
        $this->assertArrayHasKey('interpretacion', $calculo);

        $indices = $calculo['indices'];

        // 1. Anamorfosis = 180^2 / 130 = 32400 / 130 = 249.23
        $this->assertEquals(249.23, $indices['anamorfosis']['valor']);

        // 2. Corporal = (150 / 180) * 100 = 83.33 (Brevilíneo < 85)
        $this->assertEquals(83.33, $indices['corporal']['valor']);
        $this->assertEquals('Brevilíneo', $indices['corporal']['clasificacion']);

        // 3. Pelviano = (50 / 45) * 100 = 111.11 (Pelvis Ancha > 110)
        $this->assertEquals(111.11, $indices['pelviano']['valor']);
        $this->assertEquals('Pelvis Ancha', $indices['pelviano']['clasificacion']);

        // 4. Proporcionalidad = (130 / 150) * 100 = 86.67
        $this->assertEquals(86.67, $indices['proporcionalidad']['valor']);

        // 5. Dáctilo-Torácico = (20 / 180) * 100 = 11.11 (Esqueleto Medio / Eumétrico)
        $this->assertEquals(11.11, $indices['dactilo_toracico']['valor']);
        $this->assertEquals('Esqueleto Medio / Eumétrico', $indices['dactilo_toracico']['clasificacion']);

        // 6. Pelviano Transversal = (50 / 130) * 100 = 38.46
        $this->assertEquals(38.46, $indices['pelviano_transversal']['valor']);

        // 7. Pelviano Longitudinal = (45 / 130) * 100 = 34.62
        $this->assertEquals(34.62, $indices['pelviano_longitudinal']['valor']);

        // Interpretación general
        $this->assertEquals(7, $calculo['interpretacion']['total_calculados']);
        $this->assertEquals('Brevilíneo', $calculo['interpretacion']['biotipo']);
    }

    /**
     * Prueba el manejo seguro de datos incompletos y división por cero sin lanzar excepciones.
     */
    public function test_calcular_indices_with_zero_or_null_values_handles_division_by_zero_safely()
    {
        $medidaParcial = [
            'altura_hc'       => 0, // Cero alzada
            'altura_hg'       => null,
            'perimetro_pt'    => 180.0,
            'perimetro_pca'   => null,
            'longitud_lc'     => null, // Nulo
            'longitud_lg'     => 45.0,
            'anchura_ag'      => 50.0,
        ];

        $calculo = $this->zoometriaService->calcularIndices($medidaParcial);

        $this->assertNull($calculo['indices']['anamorfosis']['valor']);
        $this->assertNull($calculo['indices']['corporal']['valor']);
        $this->assertEquals(111.11, $calculo['indices']['pelviano']['valor']);
        $this->assertNull($calculo['indices']['proporcionalidad']['valor']);
        $this->assertNull($calculo['indices']['dactilo_toracico']['valor']);
        $this->assertNull($calculo['indices']['pelviano_transversal']['valor']);
        $this->assertNull($calculo['indices']['pelviano_longitudinal']['valor']);

        $this->assertEquals(1, $calculo['interpretacion']['total_calculados']);
    }

    /**
     * Prueba clasificaciones zootécnicas de biotipos (Mediolíneo y Longilíneo).
     */
    public function test_calcular_indices_biotypes_classification()
    {
        // Mediolíneo: IC entre 85 y 90 (LC = 87, PT = 100 -> IC = 87)
        $medidaMedio = [
            'altura_hc'       => 120.0,
            'perimetro_pt'    => 100.0,
            'longitud_lc'     => 87.0,
            'longitud_lg'     => 40.0,
            'anchura_ag'      => 42.0,
            'perimetro_pca'   => 10.0,
        ];
        $calculoMedio = $this->zoometriaService->calcularIndices($medidaMedio);
        $this->assertEquals('Mediolíneo', $calculoMedio['indices']['corporal']['clasificacion']);
        $this->assertEquals('Pelvis Cuadrada / Equilibrada', $calculoMedio['indices']['pelviano']['clasificacion']);
        $this->assertEquals('Esqueleto Ligero / Fino', $calculoMedio['indices']['dactilo_toracico']['clasificacion']);

        // Longilíneo: IC > 90 (LC = 95, PT = 100 -> IC = 95)
        $medidaLargo = [
            'altura_hc'       => 120.0,
            'perimetro_pt'    => 100.0,
            'longitud_lc'     => 95.0,
            'longitud_lg'     => 40.0,
            'anchura_ag'      => 36.0,
            'perimetro_pca'   => 12.5,
        ];
        $calculoLargo = $this->zoometriaService->calcularIndices($medidaLargo);
        $this->assertEquals('Longilíneo', $calculoLargo['indices']['corporal']['clasificacion']);
        $this->assertEquals('Pelvis Estrecha / Alargada', $calculoLargo['indices']['pelviano']['clasificacion']);
        $this->assertEquals('Esqueleto Fuerte / Robusto', $calculoLargo['indices']['dactilo_toracico']['clasificacion']);
    }

    /**
     * Prueba el endpoint V2 GET /api/medidas-corporales/{id}/indices con respuesta exitosa 200.
     */
    public function test_get_indices_by_medida_id_endpoint_v2_successfully()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->withHeaders(['X-API-VERSION' => '2'])
            ->getJson("/api/medidas-corporales/{$this->medida->id}/indices");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Índices corporales calculados exitosamente',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'medida_id',
                    'animal' => ['id', 'nombre', 'codigo_animal', 'sexo'],
                    'medidas_base' => ['altura_hc', 'perimetro_pt', 'longitud_lc'],
                    'indices' => [
                        'anamorfosis',
                        'corporal',
                        'pelviano',
                        'proporcionalidad',
                        'dactilo_toracico',
                        'pelviano_transversal',
                        'pelviano_longitudinal',
                    ],
                    'interpretacion' => ['biotipo', 'pelvis_conformacion', 'esqueleto_tipo', 'total_calculados'],
                ]
            ]);
    }

    /**
     * Prueba error 404 al consultar índices de una medida no existente.
     */
    public function test_get_indices_by_medida_id_not_found_returns_404()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->withHeaders(['X-API-VERSION' => '2'])
            ->getJson("/api/medidas-corporales/99999/indices");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Medidas corporales no encontradas',
            ]);
    }

    /**
     * Prueba error 403 al consultar índices sin permisos o sin acceso a la finca.
     */
    public function test_get_indices_unauthorized_user_returns_403()
    {
        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->withHeaders(['X-API-VERSION' => '2'])
            ->getJson("/api/medidas-corporales/{$this->medida->id}/indices");

        $response->assertStatus(403);
    }

    /**
     * Prueba el endpoint V2 GET /api/animales/{id}/indices-corporales (evolución histórica).
     */
    public function test_get_evolucion_indices_by_animal_v2_successfully()
    {
        // Registrar una segunda medida posterior
        MedidasCorporales::create([
            'animal_etapa_id' => $this->etapaAnimal->id,
            'altura_hc'       => 135.0,
            'altura_hg'       => 140.0,
            'perimetro_pt'    => 190.0,
            'perimetro_pca'   => 21.0,
            'longitud_lc'     => 155.0,
            'longitud_lg'     => 48.0,
            'anchura_ag'      => 52.0,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->withHeaders(['X-API-VERSION' => '2'])
            ->getJson("/api/animales/{$this->animal->id}/indices-corporales");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Evolución de índices corporales obtenida exitosamente',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'animal' => ['id', 'nombre', 'codigo_animal', 'sexo', 'finca'],
                    'total_mediciones',
                    'ultimo_analisis',
                    'historial',
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(2, $data['total_mediciones']);
        $this->assertCount(2, $data['historial']);
    }

    /**
     * Prueba error 404 en evolución si el animal no existe.
     */
    public function test_get_evolucion_indices_animal_not_found_returns_404()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->withHeaders(['X-API-VERSION' => '2'])
            ->getJson("/api/animales/99999/indices-corporales");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Animal no encontrado',
            ]);
    }
}
