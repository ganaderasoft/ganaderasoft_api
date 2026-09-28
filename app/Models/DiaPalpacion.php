<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiaPalpacion extends Model
{
    use HasFactory;

    protected $table = 'dia_palpacions';

    protected $fillable = [
        'dias',
    ];

    /**
     * Obtener los registros de preñez asociados a este día de palpación.
     */
    public function prenezDias()
    {
        return $this->hasMany(PrenezDia::class, 'dia_palpacion_id', 'id');
    }
}
