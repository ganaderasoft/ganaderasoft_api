<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lactancia extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_etapa_id',
        'fecha_inicio',
        'fecha_fin',
        'secado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'secado' => 'date',
    ];

    /**
     * Obtener el registro etapa animal asociado a esta lactancia.
     */
    public function etapaAnimal()
    {
        return $this->belongsTo(EtapaAnimal::class, 'animal_etapa_id', 'id');
    }

    /**
     * Obtener la etapa asociada a esta lactancia a través de etapa animal.
     */
    public function etapa()
    {
        return $this->hasOneThrough(Etapa::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'etapa_id');
    }

    /**
     * Obtener el animal asociado a esta lactancia a través de etapa animal.
     */
    public function animal()
    {
        return $this->hasOneThrough(Animal::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'animal_id');
    }

    /**
     * Obtener los registros de pesaje de leche para esta lactancia.
     */
    public function lecheRecords()
    {
        return $this->hasMany(Leche::class, 'lactancia_id', 'id');
    }

    /**
     * Filtro para buscar lactancias activas (sin fecha de fin).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('fecha_fin');
    }

    /**
     * Filtro para buscar por un rango de fechas.
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('fecha_inicio', [$startDate, $endDate]);
        }

        return $query->where('fecha_inicio', '>=', $startDate);
    }

    /**
     * Filtro para buscar lactancias por animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->whereHas('etapaAnimal', function ($q) use ($animalId) {
            $q->where('animal_id', $animalId);
        });
    }
}
