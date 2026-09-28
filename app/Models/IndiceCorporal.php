<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndiceCorporal extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_etapa_id',
        'anamorfosis',
        'corporal',
        'pelviano',
        'proporcionalidad',
        'dactilo_toracico',
        'pelviano_transversal',
        'pelviano_longitudinal',
    ];

    /**
     * Obtener el registro etapa animal asociado a este índice corporal.
     */
    public function etapaAnimal()
    {
        return $this->belongsTo(EtapaAnimal::class, 'animal_etapa_id', 'id');
    }
}
