<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'module',
        'action',
    ];

    /**
     * Obtener los roles asociados a este permiso.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }
}
