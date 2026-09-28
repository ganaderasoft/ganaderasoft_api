<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PesoCorporal;
use App\Models\MedidasCorporales;
use App\Models\CambiosAnimal;
use App\Models\EtapaAnimal;
use App\Models\Animal;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class PesoCorporalRelationshipTest extends TestCase
{
    public function test_peso_corporal_can_load_etapa_animal_relationship()
    {
        // Create a peso corporal instance
        $pesoCorporal = new PesoCorporal([
            'fecha_peso' => '2024-01-01',
            'peso' => 100.5,
            'comentario' => 'Test',
            'animal_etapa_id' => 1,
        ]);
        
        // Test that the relationship is defined correctly
        $relation = $pesoCorporal->etapaAnimal();
        
        // This should not throw an error
        $this->assertInstanceOf(BelongsTo::class, $relation);
        
        // Verify the relationship configuration
        $this->assertEquals(EtapaAnimal::class, $relation->getRelated()::class);
        $this->assertEquals('animal_etapa_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
    }

    public function test_peso_corporal_can_load_animal_relationship()
    {
        // Create a peso corporal instance
        $pesoCorporal = new PesoCorporal([
            'fecha_peso' => '2024-01-01',
            'peso' => 100.5,
            'comentario' => 'Test',
            'animal_etapa_id' => 1,
        ]);
        
        // Test that the relationship is defined correctly
        $relation = $pesoCorporal->animal();
        
        // This should not throw an error
        $this->assertInstanceOf(HasOneThrough::class, $relation);
        
        // Verify the relationship configuration
        $this->assertEquals(Animal::class, $relation->getRelated()::class);
    }

    public function test_medidas_corporales_can_load_etapa_animal_relationship()
    {
        // Create a medidas corporales instance
        $medidasCorporales = new MedidasCorporales([
            'altura_hc' => 120.5,
            'animal_etapa_id' => 1,
        ]);
        
        // Test that the relationship is defined correctly
        $relation = $medidasCorporales->etapaAnimal();
        
        // This should not throw an error
        $this->assertInstanceOf(BelongsTo::class, $relation);
        
        // Verify the relationship configuration
        $this->assertEquals(EtapaAnimal::class, $relation->getRelated()::class);
        $this->assertEquals('animal_etapa_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
    }

    public function test_cambios_animal_can_load_etapa_animal_relationship()
    {
        // Create a cambios animal instance
        $cambiosAnimal = new CambiosAnimal([
            'fecha_cambio' => '2024-01-01',
            'etapa_cambio' => 'Test',
            'peso' => 100.5,
            'altura' => 120.0,
            'animal_etapa_id' => 1,
        ]);
        
        // Test that the relationship is defined correctly
        $relation = $cambiosAnimal->etapaAnimal();
        
        // This should not throw an error
        $this->assertInstanceOf(BelongsTo::class, $relation);
        
        // Verify the relationship configuration
        $this->assertEquals(EtapaAnimal::class, $relation->getRelated()::class);
        $this->assertEquals('animal_etapa_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
    }
}