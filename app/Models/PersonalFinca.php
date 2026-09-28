<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalFinca extends Model
{
    use HasFactory;

    protected $table = 'personal_fincas';

    protected $fillable = [
        'finca_id',
        'persona_id',
        'tipo_trabajador_id',
        'status',
        'fecha_ingreso',
    ];

    /**
     * Obtener la finca a la que pertenece el personal.
     */
    public function finca()
    {
        return $this->belongsTo(Finca::class, 'finca_id', 'id');
    }

    /**
     * Obtener la persona asociada a este personal de finca.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id', 'id');
    }

    /**
     * Obtener el tipo de trabajador asociado a este personal de finca.
     */
    public function tipoTrabajador()
    {
        return $this->belongsTo(TipoTrabajador::class, 'tipo_trabajador_id', 'id');
    }

    /**
     * Filtro para incluir personal de una finca específica.
     */
    public function scopeForFinca($query, $fincaId)
    {
        return $query->where('finca_id', $fincaId);
    }

    /**
     * Filtro para buscar por tipo de trabajador.
     */
    public function scopeByTipoTrabajador($query, $tipoTrabajadorId)
    {
        return $query->where('tipo_trabajador_id', $tipoTrabajadorId);
    }

    /**
     * Filtro para buscar por nombre o apellido de la persona asociada.
     */
    public function scopeByName($query, $name)
    {
        return $query->whereHas('persona', function ($q) use ($name) {
            $q->where('nombre', 'like', "%%{$name}%%")
                ->orWhere('apellido', 'like', "%%{$name}%%");
        });
    }
}
