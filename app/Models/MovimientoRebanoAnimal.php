<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoRebanoAnimal extends Model
{
    use HasFactory;

    protected $table = 'animal_movimiento_rebano';

    protected $fillable = [
        'animal_id',
        'movimiento_rebano_id',
        'estado',
    ];

    /**
     * Obtener animal asociado/a.
     */
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'id');
    }

    /**
     * Obtener movimiento asociado/a.
     */
    public function movimiento()
    {
        return $this->belongsTo(MovimientoRebano::class, 'movimiento_rebano_id', 'id');
    }
}
