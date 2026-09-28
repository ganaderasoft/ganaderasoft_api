<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioVacuno extends Model
{
    use HasFactory;

    protected $fillable = [
        'finca_id',
        'num_becerra',
        'num_mauta',
        'num_novilla',
        'num_vaca',
        'num_becerro',
        'num_maute',
        'num_torete',
        'num_toro',
        'fecha_inventario',
    ];

    protected $casts = [
        'num_becerra' => 'integer',
        'num_mauta' => 'integer',
        'num_novilla' => 'integer',
        'num_vaca' => 'integer',
        'num_becerro' => 'integer',
        'num_maute' => 'integer',
        'num_torete' => 'integer',
        'num_toro' => 'integer',
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
     * Obtener el atributo total.
     */
    public function getTotalAttribute(): int
    {
        return (int) (
            $this->num_becerra + $this->num_mauta + $this->num_novilla +
            $this->num_vaca + $this->num_becerro + $this->num_maute +
            $this->num_torete + $this->num_toro
        );
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
