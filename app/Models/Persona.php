<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;

    protected $fillable = [
        'cedula',
        'nombre',
        'apellido',
        'telefono',
        'correo',
        'fecha_nacimiento',
        'status',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    /**
     * Obtener los usuarios asociados a esta persona.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'persona_user')
            ->withTimestamps();
    }

    /**
     * Obtener el propietario asociado a esta persona.
     */
    public function propietario()
    {
        return $this->hasOne(Propietario::class, 'persona_id', 'id');
    }

    /**
     * Obtener el administrador asociado a esta persona.
     */
    public function administrador()
    {
        return $this->hasOne(Administrador::class, 'persona_id', 'id');
    }

    /**
     * Obtener el transcriptor asociado a esta persona.
     */
    public function transcriptor()
    {
        return $this->hasOne(Transcriptor::class, 'persona_id', 'id');
    }

    /**
     * Obtener los registros de personal de finca asociados a esta persona.
     */
    public function personalFincas()
    {
        return $this->hasMany(PersonalFinca::class, 'persona_id', 'id');
    }

    /**
     * Obtener los registros de vacunación aplicados por esta persona.
     */
    public function vacunacionesAplicadas()
    {
        return $this->hasMany(Vacunacion::class, 'persona_id', 'id');
    }
}
