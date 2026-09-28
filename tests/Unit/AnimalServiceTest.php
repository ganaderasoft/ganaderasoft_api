<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Animal;
use App\Models\Rebano;
use App\Models\Finca;
use App\Models\Propietario;
use App\Models\Persona;
use App\Models\User;
use App\Models\ComposicionRaza;
use App\Models\Role;
use App\Services\Animal\AnimalService;
use Illuminate\Auth\Access\AuthorizationException;

class AnimalServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected AnimalService $animalService;
    protected Rebano $rebano;
    protected ComposicionRaza $raza;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->animalService = $this->app->make(AnimalService::class);
        $this->rebano = Rebano::first() ?? $this->createTestRebano();
        $this->raza = ComposicionRaza::first() ?? $this->createTestRaza();
        
        // Obtenemos o creamos un usuario administrador para las pruebas
        $this->adminUser = User::whereHas('roles', function($q) {
            $q->whereIn('code', ['admin', 'global_admin']);
        })->first();

        if (!$this->adminUser) {
            $this->adminUser = User::factory()->create();
            $adminRole = Role::whereIn('code', ['admin', 'global_admin'])->first() 
                ?? Role::create(['name' => 'Admin', 'code' => 'admin']);
            $this->adminUser->roles()->attach($adminRole);
        }
    }

    /**
     * Crea un rebaño de prueba si no existe ninguno.
     */
    private function createTestRebano(): Rebano
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'apellido' => 'Test',
            'cedula' => 'V99999999',
            'telefono' => '12345678',
            'direccion' => 'Soporte',
            'status' => 'activo',
        ]);

        $propietario = Propietario::create([
            'persona_id' => $persona->id,
        ]);

        $finca = Finca::create([
            'propietario_id' => $propietario->id,
            'nombre' => 'Finca General',
            'explotacion_tipo' => 'Carne',
            'archivado' => false,
        ]);

        return Rebano::create([
            'finca_id' => $finca->id,
            'nombre' => 'Rebaño Test',
            'archivado' => false,
        ]);
    }

    /**
     * Crea una raza de prueba si no existe ninguna.
     */
    private function createTestRaza(): ComposicionRaza
    {
        return ComposicionRaza::create([
            'nombre' => 'Jersey Pure',
            'siglas' => 'JY',
            'pelaje' => 'Marrón Claro',
            'proposito' => 'Lechero',
            'tipo_raza' => 'Pura',
            'origen' => 'Extranjero',
            'tipo_animal_id' => 1, // Vacuno
        ]);
    }

    /**
     * Prueba que se puede registrar un animal usando el servicio.
     */
    public function test_can_store_animal_via_service()
    {
        $data = [
            'rebano_id' => $this->rebano->id,
            'nombre' => 'Animal Unitario',
            'codigo_animal' => 'UNIT-9876',
            'sexo' => 'H',
            'fecha_nacimiento' => '2024-05-01',
            'procedencia' => 'Compra',
            'composicion_raza_id' => $this->raza->id,
        ];

        [$animal, $clasificacion] = $this->animalService->storeAnimal($data, $this->adminUser);

        $this->assertNotNull($animal);
        $this->assertEquals('Animal Unitario', $animal->nombre);
        $this->assertEquals('UNIT-9876', $animal->codigo_animal);
        $this->assertEquals('H', $animal->sexo);
        $this->assertDatabaseHas('animals', ['codigo_animal' => 'UNIT-9876']);
    }

    /**
     * Prueba que el listado de animales retorna resultados paginados.
     */
    public function test_can_list_animals_via_service()
    {
        $results = $this->animalService->listAnimals([], $this->adminUser);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $results);
    }
}
