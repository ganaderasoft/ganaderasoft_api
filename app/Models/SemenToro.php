<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SemenToro extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'estado',
        'fecha',
        'cantidad_pajuelas',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'fecha' => 'date',
        'cantidad_pajuelas' => 'integer',
    ];

    /**
     * Obtener toro asociado/a.
     */
    public function toro()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'id');
    }

    /**
     * Obtener servicios asociado/a.
     */
    public function servicios()
    {
        return $this->hasMany(ServicioAnimal::class, 'semen_toro_id', 'id');
    }

    /**
     * Filtro para buscar por activo.
     */
    public function scopeActivo($query)
    {
        return $query->where('estado', true);
    }

    /**
     * Filtro para buscar por for toro.
     */
    public function scopeForToro($query, $toroId)
    {
        return $query->where('animal_id', $toroId);
    }
}
