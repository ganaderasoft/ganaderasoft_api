<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tratamiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'diagnostico_id',
        'plan',
        'fecha_ini',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_ini' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Obtener el diagnóstico asociado a este tratamiento.
     */
    public function diagnostico()
    {
        return $this->belongsTo(Diagnostico::class, 'diagnostico_id', 'id');
    }

    /**
     * Filtro para buscar por un rango de fechas.
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('fecha_ini', [$startDate, $endDate]);
        }

        return $query->where('fecha_ini', '>=', $startDate);
    }

    /**
     * Filtro para buscar tratamientos por diagnóstico.
     */
    public function scopeForDiagnostico($query, $diagnosticoId)
    {
        return $query->where('diagnostico_id', $diagnosticoId);
    }
}
