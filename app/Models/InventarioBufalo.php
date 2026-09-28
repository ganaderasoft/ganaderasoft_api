<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioBufalo extends Model
{
    use HasFactory;

    protected $fillable = [
        'finca_id',
        'num_becerro',
        'num_anojo',
        'num_bubilla',
        'num_bufalo',
        'fecha_inventario',
    ];

    protected $casts = [
        'fecha_inventario' => 'date',
        'num_becerro' => 'integer',
        'num_anojo' => 'integer',
        'num_bubilla' => 'integer',
        'num_bufalo' => 'integer',
    ];

    /**
     * Obtener el/la finca que posee este/a inventario bufalo.
     */
    public function finca()
    {
        return $this->belongsTo(Finca::class, 'finca_id', 'id');
    }

    /**
     * Obtener el/la total count of all buffalo types.
     */
    public function getTotalBuffaloAttribute()
    {
        return ($this->num_becerro ?? 0) +
               ($this->num_anojo ?? 0) +
               ($this->num_bubilla ?? 0) +
               ($this->num_bufalo ?? 0);
    }

    /**
     * Filtro para incluir inventarios de un/a finca específico/a.
     */
    public function scopeForFinca($query, $fincaId)
    {
        return $query->where('finca_id', $fincaId);
    }

    /**
     * Filtro para ordenar por fecha de forma descendente.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('fecha_inventario', 'desc');
    }
}
