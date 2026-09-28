<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoAnimal extends Model
{
    use HasFactory;

    protected $table = 'animal_estado_salud';

    protected $fillable = [
        'animal_id',
        'estado_salud_id',
        'fecha_ini',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_ini' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Obtener el/la animal that has this estado.
     */
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'id');
    }

    /**
     * Obtener el/la estado salud for this estado animal.
     */
    public function estadoSalud()
    {
        return $this->belongsTo(EstadoSalud::class, 'estado_salud_id', 'id');
    }

    /**
     * Filtro para incluir solo estados (no end date) activos/as.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('fecha_fin');
    }

    /**
     * Filtro para incluir solo estados for a specific animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('animal_id', $animalId);
    }

    /**
     * Filtro para filtrar por estado salud.
     */
    public function scopeByEstado($query, $estadoId)
    {
        return $query->where('estado_salud_id', $estadoId);
    }

    /**
     * Verificar si el estado está activo actualmente.
     */
    public function isActive()
    {
        return is_null($this->esan_fecha_fin) || $this->esan_fecha_fin > now()->toDateString();
    }
}
