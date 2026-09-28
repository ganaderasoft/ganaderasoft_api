<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Administrador extends Model
{
    use HasFactory;

    protected $fillable = [
        'persona_id',
    ];

    /**
     * Obtener la persona asociada a este administrador.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id', 'id');
    }
}
