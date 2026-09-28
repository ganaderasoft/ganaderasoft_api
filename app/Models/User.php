<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'foto',
        'password',
        'google_id',
        'status',
    ];

    /**
     * Los atributos que deben ocultarse para la serialización.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Los atributos que deben ser casteados.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Obtener las fincas asociadas a este usuario.
     */
    public function fincas()
    {
        return $this->belongsToMany(Finca::class, 'finca_user')
            ->withPivot(['access_level', 'is_default', 'status'])
            ->withTimestamps();
    }

    /**
     * Obtener las personas asociadas a este usuario.
     */
    public function personas()
    {
        return $this->belongsToMany(Persona::class, 'persona_user')
            ->withTimestamps();
    }

    /**
     * Obtener los roles asociados a este usuario.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Verificar si el usuario tiene un permiso específico a nivel global.
     */
    public function hasPermissionTo($permissionCode): bool
    {
        // 1. Si es global_admin o admin, puede hacer todo
        if ($this->isAdmin()) {
            return true;
        }

        // 2. Buscar si alguno de sus roles globales contiene el permiso requerido
        return $this->roles()->whereHas('permissions', function ($q) use ($permissionCode) {
            $q->where('code', $permissionCode);
        })->exists();
    }

    /**
     * Verificar si el usuario posee uno o varios roles específicos.
     *
     * @param string|array $roles Código del rol o lista de códigos
     * @return bool
     */
    public function hasRole($roles): bool
    {
        $roleList = is_array($roles) ? $roles : [$roles];

        if ($this->relationLoaded('roles')) {
            return $this->roles->pluck('code')->intersect($roleList)->isNotEmpty();
        }

        return $this->roles()->whereIn('code', $roleList)->exists();
    }

    /**
     * Verificar si el usuario es administrador.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(['admin', 'global_admin']);
    }

    /**
     * Verificar si el usuario es un propietario.
     */
    public function isPropietario(): bool
    {
        return $this->hasRole('propietario');
    }

    /**
     * Obtener el tipo de usuario (para retrocompatibilidad con frontend).
     * Como ahora pueden haber N roles dinámicos, agrupamos genéricamente.
     */
    public function getTypeUserAttribute(): string
    {
        if ($this->isAdmin()) return 'admin';
        if ($this->isPropietario()) return 'propietario';
        
        return 'empleado'; // Para todos los demás roles (Gestores, Veterinarios, etc.)
    }

    /**
     * Obtener un arreglo con los IDs de las fincas a las que el usuario tiene acceso.
     */
    public function getAllowedFincasIds(): array
    {
        $fincasIds = $this->fincas()->wherePivot('status', 'active')->pluck('fincas.id')->toArray();
        
        // Si el usuario es propietario, también tiene acceso a las fincas de las cuales es dueño
        if ($this->isPropietario() && $this->propietario) {
            $ownedFincasIds = $this->propietario->fincas()->pluck('id')->toArray();
            $fincasIds = array_unique(array_merge($fincasIds, $ownedFincasIds));
        }
        
        return $fincasIds;
    }


    /**
     * Obtener la imagen de perfil (para retrocompatibilidad).
     */
    public function getImageAttribute(): string
    {
        return 'user.png';
    }

    /**
     * Obtener el propietario asociado a través de la relación personas (si existe).
     */
    public function getPropietarioAttribute()
    {
        $persona = $this->personas()->first();
        return $persona ? $persona->propietario : null;
    }
}

