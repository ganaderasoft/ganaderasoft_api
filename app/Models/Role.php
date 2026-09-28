<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    /**
     * Obtener los usuarios asociados a este rol.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    /**
     * Obtener los permisos asociados a este rol.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }
}
