<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacunacion extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la base de datos según estándar pivote / Many-to-Many.
     */
    protected $table = 'animal_vacuna';

    protected $fillable = [
        'animal_id',
        'vacuna_id',
        'persona_id',
        'fecha',
        'dosis',
        'costo',
        'lote',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'dosis' => 'decimal:2',
        'costo' => 'decimal:2',
    ];

    /**
     * Animal al que se le aplicó la vacuna.
     */
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'id');
    }

    /**
     * Vacuna aplicada.
     */
    public function vacuna()
    {
        return $this->belongsTo(Vacuna::class, 'vacuna_id', 'id');
    }

    /**
     * Persona que aplicó la vacuna (propietario, veterinario o trabajador de finca).
     */
    public function aplicador()
    {
        return $this->belongsTo(Persona::class, 'persona_id', 'id');
    }

    /**
     * Scope para filtrar por animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('animal_id', $animalId);
    }

    /**
     * Scope para filtrar por vacuna.
     */
    public function scopeForVacuna($query, $vacunaId)
    {
        return $query->where('vacuna_id', $vacunaId);
    }

    /**
     * Scope para filtrar por rango de fechas.
     */
    public function scopeBetweenDates($query, $from, $to)
    {
        if ($from) {
            $query->where('fecha', '>=', $from);
        }
        if ($to) {
            $query->where('fecha', '<=', $to);
        }
        return $query;
    }

    /**
     * Scope para filtrar por finca (a través del animal -> rebaño -> finca).
     */
    public function scopeForFinca($query, $fincaId)
    {
        return $query->whereHas('animal.rebano', function ($q) use ($fincaId) {
            $q->where('finca_id', $fincaId);
        });
    }

    /**
     * Scope para filtrar por rebaño (a través del animal).
     */
    public function scopeForRebano($query, $rebanoId)
    {
        return $query->whereHas('animal', function ($q) use ($rebanoId) {
            $q->where('rebano_id', $rebanoId);
        });
    }
}
