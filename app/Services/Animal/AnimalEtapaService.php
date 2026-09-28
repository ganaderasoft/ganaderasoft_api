<?php

namespace App\Services\Animal;

use App\Models\Animal;
use App\Models\EtapaAnimal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AnimalEtapaService
{
    /**
     * Registra un nuevo registro histórico de etapa para el animal.
     *
     * @param int $animalId ID del animal.
     * @param array $data Datos de la etapa animal.
     * @param mixed $user Usuario que realiza la acción.
     * @return EtapaAnimal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     * @throws ConflictHttpException
     */
    public function createEtapa(int $animalId, array $data, $user): EtapaAnimal
    {
        $animal = Animal::findOrFail($animalId);

        // Control de permisos usando la política de Animal
        if ($user->cannot('update', $animal)) {
            throw new AuthorizationException('No tiene permisos para modificar el historial de etapas de este animal.');
        }

        // Verificamos si ya existe el registro de etapa para evitar duplicados debido a la clave única
        $existingEtapaAnimal = EtapaAnimal::where('animal_id', $animal->id)
            ->where('etapa_id', $data['etapa_id'])
            ->first();

        if ($existingEtapaAnimal) {
            throw new ConflictHttpException('Ya existe un registro de etapa animal para esta etapa.');
        }

        // Si se crea una etapa activa (sin fecha de fin), cerramos cualquier otra activa
        if (empty($data['fecha_fin'])) {
            EtapaAnimal::where('animal_id', $animal->id)
                ->whereNull('fecha_fin')
                ->update(['fecha_fin' => now()->toDateString()]);
        }

        $etapaAnimal = EtapaAnimal::create([
            'fecha_ini' => $data['fecha_ini'],
            'fecha_fin' => $data['fecha_fin'] ?? null,
            'animal_id' => $animal->id,
            'etapa_id'  => $data['etapa_id'],
        ]);

        return $etapaAnimal->load(['etapa', 'animal']);
    }

    /**
     * Actualiza las fechas de una etapa animal existente.
     *
     * @param int $animalId ID del animal.
     * @param int $etapaId ID de la etapa en la tabla etapas (clave foránea).
     * @param array $data Fechas a actualizar.
     * @param mixed $user Usuario.
     * @return EtapaAnimal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateEtapa(int $id, array $data, $user): EtapaAnimal
    {
        // Buscar el registro histórico
        $etapaAnimal = EtapaAnimal::with('animal.rebano.finca')->findOrFail($id);

        // Control de permisos
        if ($user->cannot('update', $etapaAnimal->animal)) {
            throw new AuthorizationException('No tiene permisos para actualizar este registro de etapa.');
        }

        // Mapeo selectivo de atributos
        $updatePayload = [];
        if (array_key_exists('fecha_ini', $data)) $updatePayload['fecha_ini'] = $data['fecha_ini'];
        if (array_key_exists('fecha_fin', $data)) $updatePayload['fecha_fin'] = $data['fecha_fin'];

        $etapaAnimal->update($updatePayload);

        return $etapaAnimal->load(['etapa', 'animal']);
    }
}
