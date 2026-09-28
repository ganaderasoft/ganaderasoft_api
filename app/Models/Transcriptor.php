<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transcriptor extends Model
{
    use HasFactory;

    protected $fillable = [
        'persona_id',
        'tipo_transcriptor',
    ];

    /**
     * Obtener la persona asociada a este transcriptor.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id', 'id');
    }

    /**
     * Obtener las afiliaciones asociadas a este transcriptor.
     */
    public function afiliaciones()
    {
        return $this->hasMany(Afiliacion::class, 'transcriptor_id', 'id');
    }
}
