<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CasaComercial extends Model
{
    use HasFactory;

    protected $fillable = [
        'laboratorio',
        'marca_comercial',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    /**
     * Obtener las vacunas asociadas a esta casa comercial.
     */
    public function vacunas()
    {
        return $this->belongsToMany(
            Vacuna::class,
            'casa_comercial_vacuna',
            'casa_comercial_id',
            'vacuna_id'
        )->withPivot('dosis_cantidad');
    }

    /**
     * Filtro para buscar por laboratorio.
     */
    public function scopeByLaboratorio($query, $laboratorio)
    {
        return $query->where('laboratorio', 'like', "%{$laboratorio}%");
    }

    /**
     * Filtro para incluir solo las casas comerciales activas.
     */
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }
}
