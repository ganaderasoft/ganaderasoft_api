<?php

namespace App\Services\Animal;

use App\Models\PesoCorporal;
use App\Models\Animal;
use App\Services\Animal\EtapaClassifierService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

use App\Services\BaseService;

class PesoCorporalService extends BaseService
{
    /**
     * Constructor del servicio.
     * Inyecta el clasificador de etapas.
     *
     * @param EtapaClassifierService $etapaClassifier
     */
    public function __construct(
        protected EtapaClassifierService $etapaClassifier
    ) {
    }

    /**
     * Obtiene el listado de pesajes corporales aplicando filtros.
     *
     * @param array $filters Filtros.
     * @param mixed $user Usuario.
     * @return LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function listPesos(array $filters, $user)
    {

        if ($user->cannot('readAny', PesoCorporal::class)) {
            throw new AuthorizationException('Sin permisos para listar los pesos corporales.');
        }

        $query = PesoCorporal::with(['etapaAnimal.etapa', 'etapaAnimal.animal']);

        if (!empty($filters['animal_id'])) {
            $query->whereHas('etapaAnimal', function ($q) use ($filters) {
                $q->where('animal_id', $filters['animal_id']);
            });
        }

        if (!empty($filters['fecha_inicio'])) {
            $endDate = $filters['fecha_fin'] ?? null;
            $query->byDateRange($filters['fecha_inicio'], $endDate);
        }

        $this->applyFincaFilter($query, $user, 'etapaAnimal.animal.rebano');

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Registra un nuevo pesaje corporal y gatilla la reclasificación de etapa.
     *
     * @param array $data Datos.
     * @param mixed $user Usuario.
     * @return array Contiene el modelo creado ('peso') y el resultado de la clasificación ('clasificacion_etaria')
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     * @throws UnprocessableEntityHttpException
     */
    public function createPeso(array $data, $user): array
    {
        $animal = Animal::findOrFail($data['animal_id']);

        if ($user->cannot('create', [PesoCorporal::class, $animal->id])) {
            throw new AuthorizationException('No tiene permisos para registrar peso a este animal.');
        }

        // Ejecutar el clasificador biológico para ver en qué etapa debe estar el animal
        $clasificacion = $this->etapaClassifier->syncCurrentEtapa($animal, (float) $data['peso']);
        $resolvedEtapaId = $clasificacion['target_etapa_id'] ?? ($data['etapa_id'] ?? null);

        if (!$resolvedEtapaId) {
            throw new UnprocessableEntityHttpException('No se pudo determinar la etapa del animal para registrar el peso.');
        }

        // Verificar si la relación animal_etapa existe en V2 para la etapa objetivo
        $etapaAnimal = DB::table('animal_etapa')
            ->where('animal_id', $animal->id)
            ->where('etapa_id', $resolvedEtapaId)
            ->first();

        if (!$etapaAnimal) {
            throw new UnprocessableEntityHttpException('La relación etapa-animal no existe para la etapa objetivo.');
        }

        $pesoCorporal = PesoCorporal::create([
            'fecha_peso'      => $data['fecha_peso'],
            'peso'            => $data['peso'],
            'comentario'      => $data['comentario'] ?? null,
            'animal_etapa_id' => $etapaAnimal->id,
        ]);

        return [
            'peso'                 => $pesoCorporal,
            'clasificacion_etaria' => $clasificacion
        ];
    }

    /**
     * Obtiene un registro de pesaje por su ID.
     *
     * @param int $id ID.
     * @param mixed $user Usuario.
     * @return PesoCorporal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getPesoById(int $id, $user): PesoCorporal
    {
        $pesoCorporal = PesoCorporal::with(['etapaAnimal.etapa', 'etapaAnimal.animal.rebano.finca'])->findOrFail($id);

        if ($user->cannot('read', $pesoCorporal)) {
            throw new AuthorizationException('No tiene permisos para ver este peso corporal.');
        }

        return $pesoCorporal;
    }

    /**
     * Actualiza un pesaje corporal existente.
     *
     * @param int $id ID.
     * @param array $data Datos a actualizar.
     * @param mixed $user Usuario.
     * @return array Contiene el modelo actualizado ('peso') y la clasificación actual ('clasificacion_etaria')
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updatePeso(int $id, array $data, $user): array
    {
        $pesoCorporal = PesoCorporal::with(['etapaAnimal.animal.rebano.finca'])->findOrFail($id);
        $animal = $pesoCorporal->etapaAnimal->animal;

        if ($user->cannot('update', $pesoCorporal)) {
            throw new AuthorizationException('No tiene permisos para editar este peso corporal.');
        }

        $payload = [];
        if (array_key_exists('fecha_peso', $data)) $payload['fecha_peso'] = $data['fecha_peso'];
        if (array_key_exists('peso', $data)) $payload['peso'] = $data['peso'];
        if (array_key_exists('comentario', $data)) $payload['comentario'] = $data['comentario'];

        $pesoCorporal->update($payload);

        // Volver a calcular y sincronizar la etapa del animal basada en su último peso
        $latestPesoRecord = PesoCorporal::whereHas('etapaAnimal', function ($q) use ($animal) {
                $q->where('animal_id', $animal->id);
            })
            ->orderByDesc('fecha_peso')
            ->orderByDesc('id') // Para desempatar si hay varios el mismo día
            ->first(['id', 'peso']);

        $latestWeight = $latestPesoRecord ? $latestPesoRecord->peso : null;
        $isLatestPeso = $latestPesoRecord && $latestPesoRecord->id === $pesoCorporal->id;

        $clasificacion = $this->etapaClassifier->syncCurrentEtapa($animal, $latestWeight !== null ? (float) $latestWeight : null);

        // Actualizar el registro para que pertenezca a la tabla pivote de la etapa correcta
        // SOLO si el pesaje que estamos editando es el último (es decir, rige la etapa actual)
        if ($isLatestPeso) {
            $resolvedEtapaId = $clasificacion['target_etapa_id'] ?? null;
            if ($resolvedEtapaId) {
                $etapaAnimal = DB::table('animal_etapa')
                    ->where('animal_id', $animal->id)
                    ->where('etapa_id', $resolvedEtapaId)
                    ->first();
                    
                if ($etapaAnimal && $pesoCorporal->animal_etapa_id !== $etapaAnimal->id) {
                    $pesoCorporal->update(['animal_etapa_id' => $etapaAnimal->id]);
                }
            }
        }

        return [
            'peso'                 => $pesoCorporal,
            'clasificacion_etaria' => $clasificacion
        ];
    }

    /**
     * Elimina un registro de pesaje corporal.
     *
     * @param int $id ID.
     * @param mixed $user Usuario.
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function deletePeso(int $id, $user): bool
    {
        $pesoCorporal = PesoCorporal::with(['etapaAnimal.animal.rebano.finca'])->findOrFail($id);
        $animal = $pesoCorporal->etapaAnimal->animal;

        if ($user->cannot('delete', $pesoCorporal)) {
            throw new AuthorizationException('No tiene permisos para eliminar este peso corporal.');
        }

        $result = $pesoCorporal->delete();

        // Recalcular la etapa del animal basada en el peso más reciente que quedó después de borrar
        $latestWeight = PesoCorporal::whereHas('etapaAnimal', function ($q) use ($animal) {
                $q->where('animal_id', $animal->id);
            })
            ->orderByDesc('fecha_peso')
            ->value('peso');

        $this->etapaClassifier->syncCurrentEtapa($animal, $latestWeight !== null ? (float) $latestWeight : null);

        return $result;
    }
}
