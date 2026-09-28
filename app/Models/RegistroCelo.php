<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroCelo extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_etapa_id',
        'fecha',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    /**
     * Obtener el registro etapa animal asociado a este registro de celo.
     */
    public function etapaAnimal()
    {
        return $this->belongsTo(EtapaAnimal::class, 'animal_etapa_id', 'id');
    }

    /**
     * Obtener el animal asociado a este celo a través de etapa animal.
     */
    public function animal()
    {
        return $this->hasOneThrough(Animal::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'animal_id');
    }

    /**
     * Obtener la etapa asociada a este celo a través de etapa animal.
     */
    public function etapa()
    {
        return $this->hasOneThrough(Etapa::class, EtapaAnimal::class, 'id', 'id', 'animal_etapa_id', 'etapa_id');
    }

    /**
     * Obtener los servicios asociados a este registro de celo.
     */
    public function servicios()
    {
        return $this->hasMany(ServicioAnimal::class, 'registro_celo_id', 'id');
    }

    /**
     * Filtro para buscar registros de celo por animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->whereHas('etapaAnimal', function ($q) use ($animalId) {
            $q->where('animal_id', $animalId);
        });
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
