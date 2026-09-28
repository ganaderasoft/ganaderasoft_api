<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Animal;
use App\Models\Etapa;
use App\Models\EtapaAnimal;
use App\Models\Rebano;
use App\Models\Finca;
use App\Models\Propietario;
use App\Models\Persona;
use App\Models\ComposicionRaza;
use App\Services\Animal\EtapaClassifierService;
use Database\Seeders\TipoAnimalSeeder;
use Database\Seeders\EtapaSeeder;
use Database\Seeders\UpdateEtapaSeeder;
use Database\Seeders\ComposicionRazaSeeder;

class EtapaClassifierServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected EtapaClassifierService $service;
    protected Rebano $rebano;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new EtapaClassifierService();

        // Asegurar que las etapas básicas estén cargadas en la DB
        if (Etapa::count() === 0) {
            $this->seed(TipoAnimalSeeder::class);
            $this->seed(EtapaSeeder::class);
            $this->seed(ComposicionRazaSeeder::class);
        }

        // Obtener o crear un Rebaño válido para los animales de prueba
        $this->rebano = Rebano::first() ?? $this->createTestRebano();
    }

    /**
     * Crea una estructura de prueba básica (Persona, Propietario, Finca, Rebaño)
     * en caso de que la base de datos esté vacía.
     */
    private function createTestRebano(): Rebano
    {
        $persona = Persona::create([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'cedula' => 'V99999999',
            'telefono' => '123456',
            'direccion' => 'Finca Test',
            'status' => 'activo',
        ]);

        $propietario = Propietario::create([
            'persona_id' => $persona->id,
        ]);

        $finca = Finca::create([
            'propietario_id' => $propietario->id,
            'nombre' => 'Finca El Reto',
            'explotacion_tipo' => 'Lechero',
            'archivado' => false,
        ]);

        return Rebano::create([
            'finca_id' => $finca->id,
            'nombre' => 'Rebaño Principal',
            'archivado' => false,
        ]);
    }

    /**
     * Obtiene una composición de raza existente o crea una nueva para el tipo de animal especificado.
     */
    private function getOrCreateComposicionRaza(int $tipoAnimalId): ComposicionRaza
    {
        return ComposicionRaza::where('tipo_animal_id', $tipoAnimalId)->first() 
            ?? ComposicionRaza::create([
                'nombre' => 'Mestizo Test',
                'siglas' => 'MT',
                'pelaje' => 'Marrón',
                'proposito' => 'Doble Propósito',
                'tipo_raza' => 'Mestizo',
                'origen' => 'Nacional',
                'tipo_animal_id' => $tipoAnimalId,
            ]);
    }

    /**
     * Prueba la clasificación de un becerro macho (Vacuno, menor a 180 días).
     */
    public function test_resolve_becerro_macho()
    {
        $raza = $this->getOrCreateComposicionRaza(1); // 1 = Vacuno

        $animal = Animal::create([
            'rebano_id' => $this->rebano->id,
            'nombre' => 'Tornado',
            'codigo_animal' => 'TORN-001',
            'sexo' => 'M',
            'fecha_nacimiento' => now()->subDays(100), // 100 días de edad
            'archivado' => false,
            'composicion_raza_id' => $raza->id,
        ]);

        $result = $this->service->syncCurrentEtapa($animal);

        $this->assertTrue($result['changed']);
        $this->assertEquals('Becerro', $result['target_etapa']);
        $this->assertEquals(100, $result['age_days']);
    }

    /**
     * Prueba la clasificación de una becerra hembra (Vacuno, menor a 180 días).
     */
    public function test_resolve_becerra_hembra()
    {
        $raza = $this->getOrCreateComposicionRaza(1);

        $animal = Animal::create([
            'rebano_id' => $this->rebano->id,
            'nombre' => 'Espumita',
            'codigo_animal' => 'ESPU-002',
            'sexo' => 'H',
            'fecha_nacimiento' => now()->subDays(120),
            'archivado' => false,
            'composicion_raza_id' => $raza->id,
        ]);

        $result = $this->service->syncCurrentEtapa($animal);

        $this->assertEquals('Becerra', $result['target_etapa']);
    }

    /**
     * Prueba la clasificación de un maute macho (Vacuno, entre 180 y 548 días).
     */
    public function test_resolve_maute()
    {
        $raza = $this->getOrCreateComposicionRaza(1);

        $animal = Animal::create([
            'rebano_id' => $this->rebano->id,
            'nombre' => 'Maute Macho',
            'codigo_animal' => 'MAUT-001',
            'sexo' => 'M',
            'fecha_nacimiento' => now()->subDays(300), // 300 días (10 meses)
            'archivado' => false,
            'composicion_raza_id' => $raza->id,
        ]);

        $result = $this->service->syncCurrentEtapa($animal);

        $this->assertEquals('Maute', $result['target_etapa']);
    }

    /**
     * Prueba la clasificación de un novillo (Vacuno, más de 548 días pero peso menor a 450kg).
     */
    public function test_resolve_novillo()
    {
        $raza = $this->getOrCreateComposicionRaza(1);

        $animal = Animal::create([
            'rebano_id' => $this->rebano->id,
            'nombre' => 'Novillo Joven',
            'codigo_animal' => 'NOVI-001',
            'sexo' => 'M',
            'fecha_nacimiento' => now()->subDays(600), // 600 días (aprox 1.6 años)
            'archivado' => false,
            'composicion_raza_id' => $raza->id,
        ]);

        // Peso por debajo del límite de Toro (450kg)
        $result = $this->service->syncCurrentEtapa($animal, 350.0);

        $this->assertEquals('Novillo', $result['target_etapa']);
    }

    /**
     * Prueba la clasificación de un toro adulto (Vacuno, más de 548 días y peso mayor o igual a 450kg).
     */
    public function test_resolve_toro_por_peso()
    {
        $raza = $this->getOrCreateComposicionRaza(1);

        $animal = Animal::create([
            'rebano_id' => $this->rebano->id,
            'nombre' => 'Toro Padrillo',
            'codigo_animal' => 'TORO-777',
            'sexo' => 'M',
            'fecha_nacimiento' => now()->subDays(700),
            'archivado' => false,
            'composicion_raza_id' => $raza->id,
        ]);

        // Peso de adulto
        $result = $this->service->syncCurrentEtapa($animal, 480.0);

        $this->assertEquals('Toro', $result['target_etapa']);
    }

    /**
     * Prueba que el historial de etapas se actualice correctamente (cierre de la anterior, apertura de la nueva).
     */
    public function test_stage_history_updates_correctly()
    {
        $raza = $this->getOrCreateComposicionRaza(1);

        $animal = Animal::create([
            'rebano_id' => $this->rebano->id,
            'nombre' => 'Evolutivo',
            'codigo_animal' => 'EVOL-001',
            'sexo' => 'M',
            'fecha_nacimiento' => now()->subDays(100),
            'archivado' => false,
            'composicion_raza_id' => $raza->id,
        ]);

        // Primera clasificación (Becerro)
        $result1 = $this->service->syncCurrentEtapa($animal);
        $this->assertTrue($result1['changed']);
        $this->assertEquals('Becerro', $result1['target_etapa']);

        // Verificamos que tenga una etapa activa en animal_etapa
        $active1 = EtapaAnimal::where('animal_id', $animal->id)->whereNull('fecha_fin')->first();
        $this->assertNotNull($active1);
        $this->assertEquals('Becerro', $active1->etapa->nombre);

        // Simulamos el paso del tiempo y peso (crece a Maute)
        $animal->fecha_nacimiento = now()->subDays(300)->toDateString();
        $animal->save();

        $result2 = $this->service->syncCurrentEtapa($animal);
        $this->assertTrue($result2['changed']);
        $this->assertEquals('Maute', $result2['target_etapa']);

        // Verificamos que la etapa anterior (Becerro) se haya cerrado (fecha_fin no nula)
        $oldStage = EtapaAnimal::where('animal_id', $animal->id)
            ->where('etapa_id', $active1->etapa_id)
            ->first();
        $this->assertNotNull($oldStage->fecha_fin);

        // Verificamos la nueva etapa activa
        $active2 = EtapaAnimal::where('animal_id', $animal->id)->whereNull('fecha_fin')->first();
        $this->assertNotNull($active2);
        $this->assertEquals('Maute', $active2->etapa->nombre);
    }

    /**
     * Prueba que una vaca de más de 2.5 años (913 días) se clasifique como Vaca
     * por edad, aun si su peso es nulo o menor a 450kg.
     */
    public function test_resolve_vaca_adulta_sin_peso_por_edad()
    {
        $raza = $this->getOrCreateComposicionRaza(1);

        $animal = Animal::create([
            'rebano_id' => $this->rebano->id,
            'nombre' => 'Vaca Vieja',
            'codigo_animal' => 'VACA-999',
            'sexo' => 'H',
            'fecha_nacimiento' => now()->subDays(1000), // 1000 días (adulta por edad)
            'archivado' => false,
            'composicion_raza_id' => $raza->id,
        ]);

        $result = $this->service->syncCurrentEtapa($animal);

        $this->assertEquals('Vaca', $result['target_etapa']);
    }

    /**
     * Prueba que una búfala hembra en el vacío de edad (800 días) sea clasificada
     * correctamente como Añoja (ajuste dinámico de la brecha).
     */
    public function test_resolve_bufala_hembra_en_brecha_de_edad()
    {
        $raza = $this->getOrCreateComposicionRaza(2); // 2 = Búfala

        $animal = Animal::create([
            'rebano_id' => $this->rebano->id,
            'nombre' => 'Bufala Joven',
            'codigo_animal' => 'BUF-080',
            'sexo' => 'H',
            'fecha_nacimiento' => now()->subDays(800), // 800 días (en la brecha)
            'archivado' => false,
            'composicion_raza_id' => $raza->id,
        ]);

        $result = $this->service->syncCurrentEtapa($animal);

        $this->assertEquals('Añoja', $result['target_etapa']);
    }
}
