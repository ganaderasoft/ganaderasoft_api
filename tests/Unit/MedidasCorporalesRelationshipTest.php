<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\MedidasCorporales;
use App\Models\EtapaAnimal;
use App\Models\Animal;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class MedidasCorporalesRelationshipTest extends TestCase
{
    public function test_medidas_corporales_can_load_etapa_animal_relationship()
    {
        // Create a medidas corporales instance
        $medidasCorporales = new MedidasCorporales([
            'altura_hc' => 120.5,
            'altura_hg' => 115.3,
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

    public function test_medidas_corporales_can_load_animal_relationship()
    {
        // Create a medidas corporales instance
        $medidasCorporales = new MedidasCorporales([
            'altura_hc' => 120.5,
            'altura_hg' => 115.3,
            'animal_etapa_id' => 1,
        ]);
        
        // Test that the relationship is defined correctly
        $relation = $medidasCorporales->animal();
        
        // This should not throw an error
        $this->assertInstanceOf(HasOneThrough::class, $relation);
        
        // Verify the relationship configuration
        $this->assertEquals(Animal::class, $relation->getRelated()::class);
    }
}