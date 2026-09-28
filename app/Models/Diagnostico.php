<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnostico extends Model
{
    use HasFactory;

    protected $fillable = [
        'descripcion',
        'tipo',
        'fecha',
        'animal_etapa_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    /**
     * Obtener el registro etapa animal asociado a este diagnóstico.
     */
    public function etapaAnimal()
    {
        return $this->belongsTo(EtapaAnimal::class, 'animal_etapa_id', 'id');
    }

    /**
     * Obtener el animal asociado a este diagnóstico a través de etapa animal.
     */
    public function animal()
    {
        return $this->hasOneThrough(Animal::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'animal_id');
    }

    /**
     * Obtener la etapa asociada a este diagnóstico a través de etapa animal.
     */
    public function etapa()
    {
        return $this->hasOneThrough(Etapa::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'etapa_id');
    }

    /**
     * Obtener los tratamientos asociados a este diagnóstico.
     */
    public function tratamientos()
    {
        return $this->hasMany(Tratamiento::class, 'diagnostico_id', 'id');
    }

    /**
     * Filtro para buscar diagnósticos por un animal específico.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->whereHas('etapaAnimal', function ($q) use ($animalId) {
            $q->where('animal_id', $animalId);
        });
    }

    /**
     * Filtro para buscar por tipo de diagnóstico.
     */
    public function scopeByTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Filtro para buscar por un rango de fechas.
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('fecha', [$startDate, $endDate]);
        }

        return $query->where('fecha', '>=', $startDate);
    }
}
