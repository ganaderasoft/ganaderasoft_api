<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Terreno extends Model
{
    use HasFactory;

    protected $fillable = [
        'finca_id',
        'superficie',
        'relieve',
        'suelo_textura',
        'ph_suelo',
        'precipitacion',
        'velocidad_viento',
        'temp_anual',
        'temp_min',
        'temp_max',
        'radiacion',
        'fuente_agua',
        'caudal_disponible',
        'riego_metodo',
    ];

    protected $casts = [
        'superficie' => 'float',
        'precipitacion' => 'float',
        'velocidad_viento' => 'float',
        'radiacion' => 'float',
        'caudal_disponible' => 'integer',
    ];

    /**
     * Obtener la finca a la que pertenece este terreno.
     */
    public function finca()
    {
        return $this->belongsTo(Finca::class, 'finca_id', 'id');
    }

    /**
     * Filtro para filtrar por finca.
     */
    public function scopeForFinca($query, $fincaId)
    {
        return $query->where('finca_id', $fincaId);
    }

    /**
     * Filtro para filtrar por relieve.
     */
    public function scopeByRelieve($query, $relieve)
    {
        return $query->where('relieve', $relieve);
    }

    /**
     * Filtro para filtrar por fuente de agua.
     */
    public function scopeByFuenteAgua($query, $fuenteAgua)
    {
        return $query->where('fuente_agua', $fuenteAgua);
    }
}
