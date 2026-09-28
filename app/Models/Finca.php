<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Finca extends Model
{
    use HasFactory;

    protected $fillable = [
        'propietario_id',
        'nombre',
        'explotacion_tipo',
        'archivado',
    ];

    protected $casts = [
        'archivado' => 'boolean',
    ];

    /**
     * Obtener el/la propietario que posee este/a finca.
     */
    public function propietario()
    {
        return $this->belongsTo(Propietario::class, 'propietario_id', 'id');
    }

    /**
     * Obtener los usuarios asociados a esta finca.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'finca_user')
            ->withPivot(['access_level', 'is_default', 'status'])
            ->withTimestamps();
    }

    /**
     * Filtro para incluir solo fincas activos/as.
     */
    public function scopeActive($query)
    {
        return $query->where('archivado', false);
    }

    /**
     * Filtro para incluir fincas de un/a propietario específico/a.
     */
    public function scopeForPropietario($query, $propietarioId)
    {
        return $query->where('propietario_id', $propietarioId);
    }

    /**
     * Obtener el/la inventario bufalo para este/a finca.
     */
    public function inventariosBufalo()
    {
        return $this->hasMany(InventarioBufalo::class, 'finca_id', 'id');
    }

    /**
     * Obtener el/la rebanos para este/a finca.
     */
    public function rebanos()
    {
        return $this->hasMany(Rebano::class, 'finca_id', 'id');
    }

    /**
     * Obtener las afiliaciones asociadas a esta finca.
     */
    public function afiliaciones()
    {
        return $this->hasMany(Afiliacion::class, 'finca_id', 'id');
    }

    /**
     * Obtener los hierros asociados a esta finca.
     */
    public function hierros()
    {
        return $this->hasMany(Hierro::class, 'finca_id', 'id');
    }

    /**
     * Obtener todos los animales a través de los rebaños.
     */
    public function animales()
    {
        return $this->hasManyThrough(Animal::class, Rebano::class, 'finca_id', 'rebano_id', 'id', 'id');
    }

    /**
     * Obtener el/la personal para este/a finca.
     */
    public function personalFinca()
    {
        return $this->hasMany(PersonalFinca::class, 'finca_id', 'id');
    }

    /**
     * Obtener el/la terreno para este/a finca.
     */
    public function terreno()
    {
        return $this->hasOne(Terreno::class, 'finca_id', 'id');
    }
}
