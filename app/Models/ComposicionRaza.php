<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComposicionRaza extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'siglas',
        'pelaje',
        'proposito',
        'tipo_raza',
        'origen',
        'caracteristica_especial',
        'proporcion_raza',
        'finca_id',
        'tipo_animal_id',
    ];

    /**
     * Obtener la finca a la que pertenece esta composición de raza.
     */
    public function finca()
    {
        return $this->belongsTo(Finca::class, 'finca_id', 'id');
    }

    /**
     * Obtener el/la tipo animal for this composicion raza.
     */
    public function tipoAnimal()
    {
        return $this->belongsTo(TipoAnimal::class, 'tipo_animal_id', 'id');
    }

    /**
     * Obtener el/la animals with this composicion raza.
     */
    public function animales()
    {
        return $this->hasMany(Animal::class, 'composicion_raza_id', 'id');
    }

    /**
     * Filtro para buscar por nombre.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('nombre', 'like', '%'.$name.'%');
    }

    /**
     * Filtro para filtrar por finca.
     */
    public function scopeForFinca($query, $fincaId)
    {
        return $query->where('finca_id', $fincaId);
    }
}
