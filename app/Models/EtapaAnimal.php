<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtapaAnimal extends Model
{
    use HasFactory;

    protected $table = 'animal_etapa';

    // Clave primaria compuesta

    protected $fillable = [
        'animal_id',
        'etapa_id',
        'fecha_ini',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_ini' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Obtener el/la animal for this etapa animal.
     */
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id', 'id');
    }

    /**
     * Obtener el/la etapa for this etapa animal.
     */
    public function etapa()
    {
        return $this->belongsTo(Etapa::class, 'etapa_id', 'id');
    }

    /**
     * Filtro para incluir solo etapas (no end date) activos/as.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('fecha_fin');
    }

    /**
     * Filtro para incluir solo etapas for a specific animal.
     */
    public function scopeForAnimal($query, $animalId)
    {
        return $query->where('animal_id', $animalId);
    }

    /**
     * Filtro para filtrar por etapa.
     */
    public function scopeByEtapa($query, $etapaId)
    {
        return $query->where('etapa_id', $etapaId);
    }

    /**
     * Verificar si la etapa está activa actualmente.
     */
    public function isActive()
    {
        return is_null($this->etan_fecha_fin) || $this->etan_fecha_fin > now()->toDateString();
    }

    /**
     * Sobrescribir el método getKeyName para claves compuestas.
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * Configurar las claves para una consulta de actualización de guardado.
     */
    protected function setKeysForSaveQuery($query)
    {
        $keys = $this->getKeyName();
        if (! is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $keyName) {
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }

        return $query;
    }

    /**
     * Obtener el valor de la clave primaria para una consulta de guardado.
     */
    protected function getKeyForSaveQuery($keyName = null)
    {
        if (is_null($keyName)) {
            $keyName = $this->getKeyName();
        }

        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }
}
