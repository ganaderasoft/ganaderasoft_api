<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etapa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'edad_ini',
        'edad_fin',
        'tipo_animal_id',
        'sexo',
    ];

    protected $casts = [
        'edad_ini' => 'integer',
        'edad_fin' => 'integer',
    ];

    /**
     * Obtener el/la tipo animal for this etapa.
     */
    public function tipoAnimal()
    {
        return $this->belongsTo(TipoAnimal::class, 'tipo_animal_id', 'id');
    }

    /**
     * Obtener los registros de etapa animal para esta etapa.
     */
    public function etapaAnimales()
    {
        return $this->hasMany(EtapaAnimal::class, 'etapa_id', 'id');
    }

    /**
     * Filtro para buscar por nombre.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('nombre', 'like', '%'.$name.'%');
    }

    /**
     * Filtro para filtrar por tipo animal.
     */
    public function scopeForTipoAnimal($query, $tipoAnimalId)
    {
        return $query->where('tipo_animal_id', $tipoAnimalId);
    }

    /**
     * Filtro para filtrar por sexo.
     */
    public function scopeBySexo($query, $sexo)
    {
        return $query->where('sexo', $sexo);
    }

    /**
     * Verificar si una edad está dentro de esta etapa.
     */
    public function includesAge($age)
    {
        if ($this->edad_fin === null) {
            return $age >= $this->edad_ini;
        }

        return $age >= $this->edad_ini && $age <= $this->edad_fin;
    }
}
