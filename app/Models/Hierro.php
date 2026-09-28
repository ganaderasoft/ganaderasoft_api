<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hierro extends Model
{
    use HasFactory;

    protected $fillable = [
        'finca_id',
        'propietario_id',
        'identificador',
        'hierro_imagen',
        'hierro_qr',
    ];

    /**
     * Obtener la finca asociada a este hierro.
     */
    public function finca()
    {
        return $this->belongsTo(Finca::class, 'finca_id', 'id');
    }

    /**
     * Obtener el propietario asociado a este hierro.
     */
    public function propietario()
    {
        return $this->belongsTo(Propietario::class, 'propietario_id', 'id');
    }
}
