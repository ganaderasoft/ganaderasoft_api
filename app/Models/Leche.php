<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leche extends Model
{
    use HasFactory;

    protected $fillable = [
        'lactancia_id',
        'fecha_pesaje',
        'pesaje_total',
    ];

    protected $casts = [
        'fecha_pesaje' => 'date',
        'pesaje_total' => 'decimal:2',
    ];

    /**
     * Obtener la lactancia asociada a este pesaje de leche.
     */
    public function lactancia()
    {
        return $this->belongsTo(Lactancia::class, 'lactancia_id', 'id');
    }

    /**
     * Obtener el animal asociado a este pesaje a través de la lactancia y etapa animal.
     */
    public function animal()
    {
        return $this->hasOneThrough(Animal::class, Lactancia::class, 'id', 'id', 'lactancia_id', 'animal_etapa_id')
            ->join('animal_etapa', 'animal_etapa.id', '=', 'lactancias.animal_etapa_id')
            ->whereColumn('animals.id', 'animal_etapa.animal_id');
    }

    /**
     * Filtro para buscar por un rango de fechas.
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('fecha_pesaje', [$startDate, $endDate]);
        }

        return $query->where('fecha_pesaje', '>=', $startDate);
    }

    /**
     * Filtro para buscar por lactancia.
     */
    public function scopeForLactancia($query, $lactanciaId)
    {
        return $query->where('lactancia_id', $lactanciaId);
    }

    /**
     * Filtro para filtrar por minimum production.
     */
    public function scopeMinProduction($query, $minAmount)
    {
        return $query->where('pesaje_total', '>=', $minAmount);
    }
}
