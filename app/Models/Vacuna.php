<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacuna extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    /**
     * Obtener las casas comerciales asociadas a esta vacuna.
     */
    public function casasComerciales()
    {
        return $this->belongsToMany(
            CasaComercial::class,
            'casa_comercial_vacuna',
            'vacuna_id',
            'casa_comercial_id'
        )->withPivot('dosis_cantidad');
    }

    /**
     * Obtener los registros de vacunación donde se aplicó esta vacuna.
     */
    public function vacunaciones()
    {
        return $this->hasMany(Vacunacion::class, 'vacuna_id', 'id');
    }

    /**
     * Filtro para buscar por nombre de vacuna.
     */
    public function scopeByNombre($query, $nombre)
    {
        return $query->where('nombre', 'like', "%{$nombre}%");
    }

    /**
     * Filtro para incluir solo las vacunas activas.
     */
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }
}
