<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedidasCorporales extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_etapa_id',
        'altura_hc',
        'altura_hg',
        'perimetro_pt',
        'perimetro_pca',
        'longitud_lc',
        'longitud_lg',
        'anchura_ag',
    ];

    protected $casts = [
        'altura_hc' => 'float',
        'altura_hg' => 'float',
        'perimetro_pt' => 'float',
        'perimetro_pca' => 'float',
        'longitud_lc' => 'float',
        'longitud_lg' => 'float',
        'anchura_ag' => 'float',
    ];

    /**
     * Obtener el registro etapa animal asociado a estas medidas corporales.
     */
    public function etapaAnimal()
    {
        return $this->belongsTo(EtapaAnimal::class, 'animal_etapa_id', 'id');
    }

    /**
     * Obtener la etapa asociada a estas medidas corporales a través de etapa animal.
     */
    public function etapa()
    {
        return $this->hasOneThrough(Etapa::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'etapa_id');
    }

    /**
     * Obtener el animal asociado a estas medidas corporales a través de etapa animal.
     */
    public function animal()
    {
        return $this->hasOneThrough(Animal::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'animal_id');
    }

    /**
     * Filtro para buscar medidas por animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->whereHas('etapaAnimal', function ($q) use ($animalId) {
            $q->where('animal_id', $animalId);
        });
    }

    /**
     * Filtro para buscar medidas por etapa.
     */
    public function scopeForEtapa($query, $etapaId)
    {
        return $query->whereHas('etapaAnimal', function ($q) use ($etapaId) {
            $q->where('etapa_id', $etapaId);
        });
    }
}
