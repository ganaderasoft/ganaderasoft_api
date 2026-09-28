<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VacunaCasa extends Model
{
    protected $table = 'casa_comercial_vacuna';

    protected $fillable = [
        'casa_comercial_id',
        'vacuna_id',
        'dosis_cantidad',
    ];

    protected $casts = [
        'dosis_cantidad' => 'float',
    ];

    /**
     * Obtener vacuna asociado/a.
     */
    public function vacuna()
    {
        return $this->belongsTo(Vacuna::class, 'vacuna_id', 'id');
    }

    /**
     * Obtener casa comercial asociado/a.
     */
    public function casaComercial()
    {
        return $this->belongsTo(CasaComercial::class, 'casa_comercial_id', 'id');
    }
}
