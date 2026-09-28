<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuerno extends Model
{
    use HasFactory;

    protected $fillable = [
        'palpacion_id',
        'tamano',
        'medicion',
        'lado',
        'iu_plano',
        'estado_sano',
        'patologia_nombre',
        'patologia_descripcion',
    ];

    protected $casts = [
        'estado_sano' => 'boolean',
    ];

    /**
     * Obtener la palpación asociada a este cuerno.
     */
    public function palpacion()
    {
        return $this->belongsTo(Palpacion::class, 'palpacion_id', 'id');
    }
}
