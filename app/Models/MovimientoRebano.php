<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoRebano extends Model
{
    use HasFactory;

    protected $fillable = [
        'finca_id',
        'rebano_id',
        'rebano_destino',
        'finca_destino_id',
        'rebano_destino_id',
        'comentario',
    ];

    /**
     * Obtener finca origen asociado/a.
     */
    public function fincaOrigen()
    {
        return $this->belongsTo(Finca::class, 'finca_id', 'id');
    }

    /**
     * Obtener rebano origen asociado/a.
     */
    public function rebanoOrigen()
    {
        return $this->belongsTo(Rebano::class, 'rebano_id', 'id');
    }

    /**
     * Obtener finca destino asociado/a.
     */
    public function fincaDestino()
    {
        return $this->belongsTo(Finca::class, 'finca_destino_id', 'id');
    }

    /**
     * Obtener rebano destino asociado/a.
     */
    public function rebanoDestino()
    {
        return $this->belongsTo(Rebano::class, 'rebano_destino_id', 'id');
    }

    /**
     * Obtener animales asociado/a.
     */
    public function animales()
    {
        return $this->hasMany(MovimientoRebanoAnimal::class, 'movimiento_rebano_id', 'id');
    }

    /**
     * Filtro para buscar por for finca.
     */
    public function scopeForFinca($query, $fincaId)
    {
        return $query->where('finca_id', $fincaId);
    }

    /**
     * Filtro para buscar por for rebano.
     */
    public function scopeForRebano($query, $rebanoId)
    {
        return $query->where('rebano_id', $rebanoId);
    }
}
