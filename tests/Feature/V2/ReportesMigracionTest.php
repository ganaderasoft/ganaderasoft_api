<?php

namespace Tests\Feature\V2;

use App\Models\Animal;
use App\Models\ArbolGen;
use App\Models\ComposicionRaza;
use App\Models\Etapa;
use App\Models\EtapaAnimal;
use App\Models\Finca;
use App\Models\Lactancia;
use App\Models\Leche;
use App\Models\Persona;
use App\Models\PesoCorporal;
use App\Models\Propietario;
use App\Models\Rebano;
use App\Models\ReproduccionAnimal;
use App\Models\Role;
use App\Models\ServicioAnimal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportesMigracionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $propietarioUser;
    protected Finca $finca;
    protected Rebano $rebano;
    protected Animal $vaca;
    protected Animal $toroPadre;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Admin
        $this->adminUser = User::factory()->create();
        $adminRole = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
        $this->adminUser->roles()->attach($adminRole->id);

        // 2. Propietario
        $this->propietarioUser = User::factory()->create();
        $propRole = Role::firstOrCreate(['code' => 'propietario'], ['name' => 'Propietario']);
        $this->propietarioUser->roles()->attach($propRole->id);

        $persona = Persona::create([
            'nombre'   => 'Carlos',
            'apellido' => 'Ganadero',
            'correo'   => $this->propietarioUser->email,
            'cedula'   => 'V' . fake()->unique()->numberBetween(10000000, 99999999),
        ]);
        $this->propietarioUser->personas()->attach($persona->id);
        $propietario = Propietario::create(['persona_id' => $persona->id]);

        // 3. Finca & Rebaño
        $this->finca = Finca::create([
            'propietario_id'   => $propietario->id,
            'nombre'           => 'Hacienda La Colina',
            'explotacion_tipo' => 'Leche',
            'archivado'        => false,
        ]);

        $this->rebano = Rebano::create([
            'finca_id'  => $this->finca->id,
            'nombre'    => 'Lote Productoras A',
            'archivado' => false,
        ]);

        $raza = ComposicionRaza::firstOrCreate(['tipo_raza' => 'Holstein']);

        // 4. Toro Padre
        $this->toroPadre = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'nombre'              => 'Toro Campeón',
            'codigo_animal'       => 'TORO-001',
            'sexo'                => 'M',
            'fecha_nacimiento'    => Carbon::now()->subYears(4)->format('Y-m-d'),
            'archivado'           => false,
            'composicion_raza_id' => $raza->id,
        ]);

        // 5. Vaca
        $this->vaca = Animal::create([
            'rebano_id'           => $this->rebano->id,
            'nombre'              => 'Vaca Mariposa',
            'codigo_animal'       => 'VAC-101',
            'sexo'                => 'H',
            'fecha_nacimiento'    => Carbon::now()->subYears(3)->format('Y-m-d'),
            'archivado'           => false,
            'composicion_raza_id' => $raza->id,
        ]);

        // Genealogía
        ArbolGen::create([
            'hijo_id'  => $this->vaca->id,
            'padre_id' => $this->toroPadre->id,
            'tipo'     => 'Padre',
        ]);

        // Etapa
        $tipoAnimal = \App\Models\TipoAnimal::firstOrCreate(['nombre' => 'Bovino']);
        $etapa = Etapa::firstOrCreate(
            ['nombre' => 'Vaca en Producción'],
            ['tipo_animal_id' => $tipoAnimal->id]
        );
        $etapaAnimal = EtapaAnimal::create([
            'animal_id' => $this->vaca->id,
            'etapa_id'  => $etapa->id,
            'fecha_ini' => Carbon::now()->subMonths(12)->format('Y-m-d'),
            'fecha_fin' => null,
        ]);

        // Pesos corporales (ingreso, intermedio, último)
        PesoCorporal::create([
            'animal_etapa_id' => $etapaAnimal->id,
            'fecha_peso'      => Carbon::now()->subMonths(10)->format('Y-m-d'),
            'peso'            => 380.0,
        ]);
        PesoCorporal::create([
            'animal_etapa_id' => $etapaAnimal->id,
            'fecha_peso'      => Carbon::now()->subMonths(5)->format('Y-m-d'),
            'peso'            => 420.0,
        ]);
        PesoCorporal::create([
            'animal_etapa_id' => $etapaAnimal->id,
            'fecha_peso'      => Carbon::now()->subMonth()->format('Y-m-d'),
            'peso'            => 450.0,
        ]);

        // Lactancia y Pesajes de leche
        $lactancia = Lactancia::create([
            'animal_etapa_id' => $etapaAnimal->id,
            'fecha_inicio'    => Carbon::now()->subDays(100)->format('Y-m-d'),
            'fecha_fin'       => null,
        ]);

        Leche::create([
            'lactancia_id' => $lactancia->id,
            'fecha_pesaje' => Carbon::now()->subDays(90)->format('Y-m-d'),
            'pesaje_total' => 18.5,
        ]);
        Leche::create([
            'lactancia_id' => $lactancia->id,
            'fecha_pesaje' => Carbon::now()->subDays(60)->format('Y-m-d'),
            'pesaje_total' => 20.0,
        ]);
        Leche::create([
            'lactancia_id' => $lactancia->id,
            'fecha_pesaje' => Carbon::now()->subDays(30)->format('Y-m-d'),
            'pesaje_total' => 19.0,
        ]);

        // Evento reproductivo: Parto
        ReproduccionAnimal::create([
            'animal_etapa_id'    => $etapaAnimal->id,
            'fecha_reproduccion' => Carbon::now()->subDays(100)->format('Y-m-d'),
            'tipo_reproduccion'  => 'Normal',
            'observacion'        => 'Nacimiento de cría hembra sana',
        ]);

        // Evento reproductivo: Servicio
        ServicioAnimal::create([
            'animal_id'   => $this->vaca->id,
            'tipo'        => 'IA',
            'fecha'       => Carbon::now()->subDays(20)->format('Y-m-d'),
            'observacion' => 'Semen importado',
        ]);
    }

    public function test_reporte_general_v2()
    {
        $response = $this->actingAs($this->propietarioUser)
            ->withHeader('X-API-VERSION', '2')
            ->getJson("/api/reportes/general?finca_id={$this->finca->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'finca' => ['id', 'nombre', 'explotacion_tipo'],
                    'total_animales',
                    'animales' => [
                        '*' => [
                            'id',
                            'codigo',
                            'nombre',
                            'sexo',
                            'categoria',
                            'estatus',
                            'edad_meses',
                            'edad_formateada',
                            'raza',
                            'peso_ingreso',
                            'fecha_ingreso',
                            'penultimo_peso',
                            'fecha_penultimo_peso',
                            'ultimo_peso',
                            'fecha_ultimo_peso',
                            'padre_id',
                            'padre_codigo',
                        ]
                    ]
                ]
            ]);

        $animales = $response->json('data.animales');
        $vacaData = collect($animales)->firstWhere('codigo', 'VAC-101');

        $this->assertNotNull($vacaData);
        $this->assertEquals(380.0, $vacaData['peso_ingreso']);
        $this->assertEquals(420.0, $vacaData['penultimo_peso']);
        $this->assertEquals(450.0, $vacaData['ultimo_peso']);
        $this->assertEquals($this->toroPadre->id, $vacaData['padre_id']);
        $this->assertEquals('TORO-001', $vacaData['padre_codigo']);
    }

    public function test_reporte_lactancias_con_algoritmo_tim_v2()
    {
        $response = $this->actingAs($this->propietarioUser)
            ->withHeader('X-API-VERSION', '2')
            ->getJson("/api/reportes/lactancias?finca_id={$this->finca->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'finca' => ['id', 'nombre'],
                    'total_animales',
                    'produccion_total_finca',
                    'animales' => [
                        '*' => [
                            'id',
                            'codigo',
                            'nombre',
                            'total_lactancias',
                            'produccion_vitalicia',
                            'lactancias' => [
                                '*' => [
                                    'id',
                                    'num_lactancia',
                                    'fecha_inicio',
                                    'fecha_fin',
                                    'estado',
                                    'dias_lactancia',
                                    'p244',
                                    'p270',
                                    'p305',
                                    'produccion_total',
                                    'total_pesajes',
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

        $animales = $response->json('data.animales');
        $vacaData = collect($animales)->firstWhere('codigo', 'VAC-101');

        $this->assertNotNull($vacaData);
        $this->assertGreaterThan(0, $vacaData['produccion_vitalicia']);
        $this->assertCount(1, $vacaData['lactancias']);
        $this->assertGreaterThan(0, $vacaData['lactancias'][0]['produccion_total']);
    }

    public function test_reporte_reproductivo_v2()
    {
        $response = $this->actingAs($this->propietarioUser)
            ->withHeader('X-API-VERSION', '2')
            ->getJson("/api/reportes/reproductivo?finca_id={$this->finca->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'finca' => ['id', 'nombre'],
                    'resumen' => [
                        'total_animales',
                        'total_eventos',
                        'total_partos',
                        'total_servicios',
                    ],
                    'animales' => [
                        '*' => [
                            'id',
                            'codigo',
                            'nombre',
                            'total_eventos',
                            'eventos' => [
                                '*' => [
                                    'id',
                                    'origen',
                                    'tipo',
                                    'fecha',
                                    'observacion',
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

        $animales = $response->json('data.animales');
        $vacaData = collect($animales)->firstWhere('codigo', 'VAC-101');

        $this->assertNotNull($vacaData);
        $this->assertEquals(2, $vacaData['total_eventos']);
        $this->assertEquals(1, $response->json('data.resumen.total_partos'));
        $this->assertEquals(1, $response->json('data.resumen.total_servicios'));
    }

    public function test_reporte_pesaje_leche_v2()
    {
        $response = $this->actingAs($this->propietarioUser)
            ->withHeader('X-API-VERSION', '2')
            ->getJson("/api/reportes/pesaje-leche?finca_id={$this->finca->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'finca' => ['id', 'nombre'],
                    'resumen' => [
                        'total_pesajes',
                        'total_produccion',
                        'promedio_pesaje',
                    ],
                    'pesajes' => [
                        '*' => [
                            'id',
                            'codigo',
                            'nombre',
                            'categoria',
                            'estatus',
                            'lote',
                            'fecha_evento',
                            'peso_total',
                        ]
                    ]
                ]
            ]);

        $pesajes = $response->json('data.pesajes');
        $this->assertCount(3, $pesajes);
        $this->assertEquals(57.5, $response->json('data.resumen.total_produccion'));
    }

    public function test_usuario_sin_acceso_a_finca_es_rechazado()
    {
        $otroUser = User::factory()->create();
        $propRole = Role::firstOrCreate(['code' => 'propietario'], ['name' => 'Propietario']);
        $otroUser->roles()->attach($propRole->id);

        $persona = Persona::create([
            'nombre'   => 'Otro',
            'apellido' => 'Usuario',
            'correo'   => $otroUser->email,
            'cedula'   => 'V' . fake()->unique()->numberBetween(10000000, 99999999),
        ]);
        $otroUser->personas()->attach($persona->id);
        Propietario::create(['persona_id' => $persona->id]);

        $response = $this->actingAs($otroUser)
            ->withHeader('X-API-VERSION', '2')
            ->getJson("/api/reportes/pesaje-leche?finca_id={$this->finca->id}");

        $response->assertStatus(403);
    }
}
