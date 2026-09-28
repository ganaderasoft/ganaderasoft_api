<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioGeneral extends Model
{
    use HasFactory;

    protected $fillable = [
        'finca_id',
        'num_personal',
        'fecha_inventario',
    ];

    protected $casts = [
        'num_personal' => 'integer',
        'fecha_inventario' => 'date',
    ];

    /**
     * Obtener finca asociado/a.
     */
    public function finca()
    {
        return $this->belongsTo(Finca::class, 'finca_id', 'id');
    }

    /**
     * Filtro para buscar por for finca.
     */
    public function scopeForFinca($query, $fincaId)
    {
        return $query->where('finca_id', $fincaId);
    }

    /**
     * Filtro para buscar por by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('fecha_inventario', [$startDate, $endDate]);
        }

        return $query->where('fecha_inventario', '>=', $startDate);
    }
}
