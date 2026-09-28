<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoAnimal extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
    ];

    /**
     * Filtro para buscar por nombre.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('nombre', 'like', '%'.$name.'%');
    }
}
