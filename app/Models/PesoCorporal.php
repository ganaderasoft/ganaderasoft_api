<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesoCorporal extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_etapa_id',
        'fecha_peso',
        'peso',
        'comentario',
    ];

    protected $casts = [
        'fecha_peso' => 'date',
        'peso' => 'float',
    ];

    /**
     * Obtener el registro de etapa animal asociado.
     * Usar whereColumn para manejar claves compuestas correctamente.
     */
    public function etapaAnimal()
    {
        return $this->belongsTo(EtapaAnimal::class, 'animal_etapa_id');
    }

    /**
     * Obtener el animal a través de la clave foránea directa.
     */
    public function animal()
    {
        return $this->hasOneThrough(Animal::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'animal_id');
    }

    /**
     * Filtro para filtrar por date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('fecha_peso', [$startDate, $endDate]);
        }

        return $query->where('fecha_peso', '>=', $startDate);
    }

    /**
     * Filtro para filtrar por animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('animal_etapa_id', $animalId);
    }
}
