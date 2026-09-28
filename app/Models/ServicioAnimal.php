<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicioAnimal extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'semen_toro_id',
        'personal_finca_id',
        'registro_celo_id',
        'tipo',
        'fecha',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    /**
     * Obtener animal asociado/a.
     */
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'id');
    }

    /**
     * Obtener semen asociado/a.
     */
    public function semen()
    {
        return $this->belongsTo(SemenToro::class, 'semen_toro_id', 'id');
    }

    /**
     * Obtener tecnico asociado/a.
     */
    public function tecnico()
    {
        return $this->belongsTo(PersonalFinca::class, 'personal_finca_id', 'id');
    }

    /**
     * Obtener registro celo asociado/a.
     */
    public function registroCelo()
    {
        return $this->belongsTo(RegistroCelo::class, 'registro_celo_id', 'id');
    }

    /**
     * Filtro para buscar por for animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('animal_id', $animalId);
    }

    /**
     * Filtro para buscar por by tipo.
     */
    public function scopeByTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Filtro para buscar por by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('fecha', [$startDate, $endDate]);
        }

        return $query->where('fecha', '>=', $startDate);
    }
}
