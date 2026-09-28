<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rebano extends Model
{
    use HasFactory;

    protected $fillable = [
        'finca_id',
        'nombre',
        'archivado',
    ];

    protected $casts = [
        'archivado' => 'boolean',
    ];

    /**
     * Obtener el/la finca que posee este/a rebano.
     */
    public function finca()
    {
        return $this->belongsTo(Finca::class, 'finca_id', 'id');
    }

    /**
     * Obtener el/la animals para este/a rebano.
     */
    public function animales()
    {
        return $this->hasMany(Animal::class, 'rebano_id', 'id');
    }

    /**
     * Filtro para incluir solo rebanos activos/as.
     */
    public function scopeActive($query)
    {
        return $query->where('archivado', false);
    }

    /**
     * Filtro para incluir rebanos de un/a finca específico/a.
     */
    public function scopeForFinca($query, $fincaId)
    {
        return $query->where('finca_id', $fincaId);
    }
}
