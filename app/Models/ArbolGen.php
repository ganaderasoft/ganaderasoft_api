<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArbolGen extends Model
{
    protected $fillable = [
        'hijo_id',
        'padre_id',
        'tipo',
    ];

    protected $casts = [
        'hijo_id' => 'integer',
        'padre_id' => 'integer',
    ];

    /** El animal hijo (el dueño de esta relación). */
    public function hijo()
    {
        return $this->belongsTo(Animal::class, 'hijo_id', 'id');
    }

    /** El animal padre/madre de este registro. */
    public function progenitor()
    {
        return $this->belongsTo(Animal::class, 'padre_id', 'id');
    }
}
