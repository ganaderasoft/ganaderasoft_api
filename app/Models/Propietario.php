<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Propietario extends Model
{
    use HasFactory;

    protected $fillable = [
        'persona_id',
    ];

    protected $casts = [
    ];

    /**
     * Obtener la persona asociada a este propietario.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id', 'id');
    }

    /**
     * Obtener el/la fincas para este/a propietario.
     */
    public function fincas()
    {
        return $this->hasMany(Finca::class, 'propietario_id', 'id');
    }

    /**
     * Obtener las afiliaciones asociadas a este propietario.
     */
    public function afiliaciones()
    {
        return $this->hasMany(Afiliacion::class, 'propietario_id', 'id');
    }

    /**
     * Obtener los hierros asociados a este propietario.
     */
    public function hierros()
    {
        return $this->hasMany(Hierro::class, 'propietario_id', 'id');
    }

    /**
     * Obtener el/la full name of the propietario.
     */
    public function getFullNameAttribute(): string
    {
        return $this->persona ? trim($this->persona->nombre . ' ' . $this->persona->apellido) : '';
    }

    /**
     * Filtro para incluir solo propietarios activos/as.
     */
    public function scopeActive($query)
    {
        return $query->whereHas('persona', function ($q) {
            $q->where('status', 'activo')->orWhere('status', 1);
        });
    }
}
