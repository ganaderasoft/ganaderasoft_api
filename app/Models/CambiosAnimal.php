<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CambiosAnimal extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_etapa_id',
        'fecha_cambio',
        'etapa_cambio',
        'peso',
        'altura',
        'comentario',
    ];

    protected $casts = [
        'fecha_cambio' => 'date',
        'peso' => 'float',
        'altura' => 'float',
    ];

    /**
     * Obtener el registro etapa animal asociado a este cambio.
     */
    public function etapaAnimal()
    {
        return $this->belongsTo(EtapaAnimal::class, 'animal_etapa_id', 'id');
    }

    /**
     * Obtener la etapa asociada a este cambio a través de etapa animal.
     */
    public function etapa()
    {
        return $this->hasOneThrough(Etapa::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'etapa_id');
    }

    /**
     * Obtener el animal asociado a este cambio a través de etapa animal.
     */
    public function animal()
    {
        return $this->hasOneThrough(Animal::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'animal_id');
    }

    /**
     * Filtro para buscar cambios por un rango de fechas.
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('fecha_cambio', [$startDate, $endDate]);
        }

        return $query->where('fecha_cambio', '>=', $startDate);
    }

    /**
     * Filtro para buscar cambios por animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->whereHas('etapaAnimal', function ($q) use ($animalId) {
            $q->where('animal_id', $animalId);
        });
    }

    /**
     * Filtro para buscar cambios por etapa.
     */
    public function scopeForEtapa($query, $etapaId)
    {
        return $query->whereHas('etapaAnimal', function ($q) use ($etapaId) {
            $q->where('etapa_id', $etapaId);
        });
    }

    /**
     * Filtro para filtrar por etapa cambio.
     */
    public function scopeByEtapaCambio($query, $etapaCambio)
    {
        return $query->where('etapa_cambio', $etapaCambio);
    }
}
