<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ovario extends Model
{
    use HasFactory;

    protected $fillable = [
        'palpacion_id',
        'medida',
        'tamano',
        'lado',
    ];

    /**
     * Obtener la palpación asociada a este ovario.
     */
    public function palpacion()
    {
        return $this->belongsTo(Palpacion::class, 'palpacion_id', 'id');
    }

    /**
     * Obtener los folículos asociados a este ovario.
     */
    public function foliculos()
    {
        return $this->belongsToMany(Foliculo::class, 'foliculo_ovario', 'ovario_id', 'foliculo_id');
    }
}
