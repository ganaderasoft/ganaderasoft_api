<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoSalud extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
    ];

    /**
     * Obtener el/la estados animal for this estado salud.
     */
    public function estadosAnimal()
    {
        return $this->hasMany(EstadoAnimal::class, 'estado_salud_id', 'id');
    }

    /**
     * Filtro para buscar por nombre.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('nombre', 'like', '%'.$name.'%');
    }
}
