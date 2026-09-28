<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReproduccionAnimal extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_etapa_id',
        'fecha_reproduccion',
        'tipo_reproduccion',
        'observacion',
    ];

    protected $casts = [
        'fecha_reproduccion' => 'date',
    ];

    /**
     * Obtener el registro etapa animal asociado a esta reproducción.
     */
    public function etapaAnimal()
    {
        return $this->belongsTo(EtapaAnimal::class, 'animal_etapa_id', 'id');
    }

    /**
     * Obtener el animal asociado a esta reproducción a través de etapa animal.
     */
    public function animal()
    {
        return $this->hasOneThrough(Animal::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'animal_id');
    }

    /**
     * Obtener la etapa asociada a esta reproducción a través de etapa animal.
     */
    public function etapa()
    {
        return $this->hasOneThrough(Etapa::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'etapa_id');
    }

    /**
     * Filtro para buscar reproducciones por animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->whereHas('etapaAnimal', function ($q) use ($animalId) {
            $q->where('animal_id', $animalId);
        });
    }

    /**
     * Filtro para buscar por by tipo.
     */
    public function scopeByTipo($query, $tipo)
    {
        return $query->where('tipo_reproduccion', $tipo);
    }

    /**
     * Filtro para buscar por by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('fecha_reproduccion', [$startDate, $endDate]);
        }

        return $query->where('fecha_reproduccion', '>=', $startDate);
    }
}
