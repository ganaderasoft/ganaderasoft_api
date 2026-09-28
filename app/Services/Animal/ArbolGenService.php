<?php

namespace App\Services\Animal;

use App\Models\Animal;
use App\Models\ArbolGen;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Services\BaseService;

class ArbolGenService extends BaseService
{
    /**
     * Construye el árbol de 3 generaciones para un animal.
     */
    public function showTree(Animal $animal, User $user): array
    {
        if ($user->cannot('readAny', ArbolGen::class)) {
            throw new AuthorizationException('No tiene permisos para ver árboles genealógicos.');
        }
        $animal->load([
            'registroPadre.progenitor.registroPadre.progenitor',
            'registroPadre.progenitor.registroMadre.progenitor',
            'registroMadre.progenitor.registroPadre.progenitor',
            'registroMadre.progenitor.registroMadre.progenitor',
            'hijos.hijo',
        ]);

        $padre = optional($animal->registroPadre)->progenitor;
        $madre = optional($animal->registroMadre)->progenitor;

        $abueloPaterno = $padre ? optional($padre->registroPadre)->progenitor : null;
        $abuelaPaterna = $padre ? optional($padre->registroMadre)->progenitor : null;
        $abueloMaterno = $madre ? optional($madre->registroPadre)->progenitor : null;
        $abuelaMaterna = $madre ? optional($madre->registroMadre)->progenitor : null;

        $hijos = $animal->hijos->map(function ($rel) {
            return $rel->hijo;
        })->filter()->values();

        return [
            'animal' => $animal,
            'padre'  => $padre ? [
                'animal' => $padre,
                'abuelo_paterno' => $abueloPaterno,
                'abuela_paterna' => $abuelaPaterna,
            ] : null,
            'madre'  => $madre ? [
                'animal' => $madre,
                'abuelo_materno' => $abueloMaterno,
                'abuela_materna' => $abuelaMaterna,
            ] : null,
            'hijos'  => $hijos,
            'relaciones' => [
                'id_arbol_padre' => optional($animal->registroPadre)->id,
                'id_arbol_madre' => optional($animal->registroMadre)->id,
            ],
        ];
    }

    /**
     * Registra o actualiza un progenitor (Padre o Madre) validando reglas de negocio.
     */
    public function store(Animal $animal, string $tipo, int $progenitorId, User $user): ArbolGen
    {
        if ($user->cannot('create', [ArbolGen::class, $animal])) {
            throw new AuthorizationException('No tiene permisos para registrar progenitores a este animal.');
        }
        if ((int)$animal->id === (int)$progenitorId) {
            throw ValidationException::withMessages(['id_padre' => 'Un animal no puede ser su propio progenitor.']);
        }

        $progenitor = Animal::findOrFail($progenitorId);

        if ($tipo === 'Padre' && $progenitor->sexo !== 'M') {
            throw ValidationException::withMessages(['id_padre' => 'El Padre debe ser un animal macho (M).']);
        }

        if ($tipo === 'Madre' && $progenitor->sexo === 'M') {
            throw ValidationException::withMessages(['id_padre' => 'La Madre debe ser un animal hembra (H).']);
        }

        return ArbolGen::updateOrCreate(
            ['hijo_id' => $animal->id, 'tipo' => $tipo],
            ['padre_id' => $progenitor->id]
        );
    }

    /**
     * Elimina la relación de progenitor.
     */
    public function destroy(Animal $animal, string $tipo, User $user): bool
    {
        if ($user->cannot('delete', [ArbolGen::class, null, $animal])) {
            throw new AuthorizationException('No tiene permisos para eliminar progenitores de este animal.');
        }
        $deleted = ArbolGen::where('hijo_id', $animal->id)
            ->where('tipo', $tipo)
            ->delete();

        return $deleted > 0;
    }

    /**
     * Obtiene los candidatos a ser progenitores excluyendo línea familiar directa.
     */
    public function getAvailableParents(Animal $animal, ?string $tipo, User $user)
    {
        if ($user->cannot('readAny', ArbolGen::class)) {
            throw new AuthorizationException('No tiene permisos para buscar candidatos a progenitores.');
        }
        $animal->load([
            'registroPadre.progenitor.registroPadre.progenitor',
            'registroPadre.progenitor.registroMadre.progenitor',
            'registroMadre.progenitor.registroPadre.progenitor',
            'registroMadre.progenitor.registroMadre.progenitor',
            'hijos',
        ]);

        $excluidos = collect([$animal->id]);

        $padre = optional($animal->registroPadre)->progenitor;
        $madre = optional($animal->registroMadre)->progenitor;
        if ($padre) $excluidos->push($padre->id);
        if ($madre) $excluidos->push($madre->id);

        if ($padre) {
            $abueloP = optional($padre->registroPadre)->progenitor;
            $abuelaP = optional($padre->registroMadre)->progenitor;
            if ($abueloP) $excluidos->push($abueloP->id);
            if ($abuelaP) $excluidos->push($abuelaP->id);
        }

        if ($madre) {
            $abueloM = optional($madre->registroPadre)->progenitor;
            $abuelaM = optional($madre->registroMadre)->progenitor;
            if ($abueloM) $excluidos->push($abueloM->id);
            if ($abuelaM) $excluidos->push($abuelaM->id);
        }

        $animal->hijos->each(fn($rel) => $excluidos->push($rel->hijo_id));

        $query = Animal::where('archivado', false)
            ->whereNotIn('id', $excluidos->unique()->values());

        if ($tipo === 'Padre') {
            $query->where('sexo', 'M');
        } elseif ($tipo === 'Madre') {
            $query->whereIn('sexo', ['F', 'H']);
        }

        return $query->orderBy('nombre')->get();
    }
}
