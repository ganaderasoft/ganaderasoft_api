<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Afiliacion extends Model
{
    use HasFactory;

    protected $fillable = [
        'propietario_id',
        'transcriptor_id',
        'finca_id',
        'estado',
        'receptor_solicitud',
    ];

    /**
     * Obtener el propietario asociado a esta afiliación.
     */
    public function propietario()
    {
        return $this->belongsTo(Propietario::class, 'propietario_id', 'id');
    }

    /**
     * Obtener el transcriptor asociado a esta afiliación.
     */
    public function transcriptor()
    {
        return $this->belongsTo(Transcriptor::class, 'transcriptor_id', 'id');
    }

    /**
     * Obtener la finca asociada a esta afiliación.
     */
    public function finca()
    {
        return $this->belongsTo(Finca::class, 'finca_id', 'id');
    }
}
