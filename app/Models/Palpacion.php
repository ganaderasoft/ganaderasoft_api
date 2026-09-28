<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Palpacion extends Model
{
    use HasFactory;

    protected $fillable = [
        'personal_finca_id',
        'tipo',
        'fecha',
        'animal_etapa_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    /**
     * Obtener el registro etapa animal asociado a esta palpación.
     */
    public function etapaAnimal()
    {
        return $this->belongsTo(EtapaAnimal::class, 'animal_etapa_id', 'id');
    }

    /**
     * Obtener el animal asociado a esta palpación a través de etapa animal.
     */
    public function animal()
    {
        return $this->hasOneThrough(Animal::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'animal_id');
    }

    /**
     * Obtener la etapa asociada a esta palpación a través de etapa animal.
     */
    public function etapa()
    {
        return $this->hasOneThrough(Etapa::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'etapa_id');
    }

    /**
     * Obtener el técnico (personal de finca) que realizó la palpación.
     */
    public function tecnico()
    {
        return $this->belongsTo(PersonalFinca::class, 'personal_finca_id', 'id');
    }

    /**
     * Obtener los registros de cuernos asociados a esta palpación.
     */
    public function cuernos()
    {
        return $this->hasMany(Cuerno::class, 'palpacion_id', 'id');
    }

    /**
     * Obtener los registros de ovarios asociados a esta palpación.
     */
    public function ovarios()
    {
        return $this->hasMany(Ovario::class, 'palpacion_id', 'id');
    }

    /**
     * Obtener los registros de preñez asociados a esta palpación.
     */
    public function prenezDias()
    {
        return $this->hasMany(PrenezDia::class, 'palpacion_id', 'id');
    }

    /**
     * Filtro para buscar palpaciones por animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->whereHas('etapaAnimal', function ($q) use ($animalId) {
            $q->where('animal_id', $animalId);
        });
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
