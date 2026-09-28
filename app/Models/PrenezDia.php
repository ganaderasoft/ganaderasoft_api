<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrenezDia extends Model
{
    use HasFactory;

    protected $table = 'prenez_dias';

    protected $fillable = [
        'dia_palpacion_id',
        'palpacion_id',
        'tamano',
    ];

    /**
     * Obtener el día de palpación asociado a este registro de preñez.
     */
    public function diaPalpacion()
    {
        return $this->belongsTo(DiaPalpacion::class, 'dia_palpacion_id', 'id');
    }

    /**
     * Obtener la palpación asociada a este registro de preñez.
     */
    public function palpacion()
    {
        return $this->belongsTo(Palpacion::class, 'palpacion_id', 'id');
    }
}
