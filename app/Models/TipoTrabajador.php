<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoTrabajador extends Model
{
    use HasFactory;

    protected $table = 'tipo_trabajadors';

    protected $fillable = [
        'nombre',
    ];

    /**
     * Obtener los registros de personal asociados a este tipo de trabajador.
     */
    public function personalFincas()
    {
        return $this->hasMany(PersonalFinca::class, 'tipo_trabajador_id', 'id');
    }

    /**
     * Filtro para buscar por nombre.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('nombre', 'like', '%' . $name . '%');
    }
}
