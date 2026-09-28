<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Foliculo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'siglas',
    ];

    /**
     * Obtener los ovarios asociados a este folículo.
     */
    public function ovarios()
    {
        return $this->belongsToMany(Ovario::class, 'foliculo_ovario', 'foliculo_id', 'ovario_id');
    }
}
