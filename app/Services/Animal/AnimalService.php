<?php

namespace App\Services\Animal;

use App\Models\Animal;
use App\Models\Rebano;
use App\Models\EstadoAnimal;
use App\Models\EtapaAnimal;
use App\Models\PesoCorporal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AnimalService extends BaseService
{
    /**
     * Constructor del servicio.
     * Inyecta el clasificador de etapas para la sincronización automática de etapa por edad y peso.
     *
     * @param EtapaClassifierService $etapaClassifier
     */
    public function __construct(
        private EtapaClassifierService $etapaClassifier
    ) {}

    /**
     * Obtiene el listado de animales activos aplicando filtros y verificando permisos del usuario.
     *
     * @param array $filters Filtros aplicados (rebano_id, sexo, etc.).
     * @param mixed $user Usuario que realiza la petición.
     * @return LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function listAnimals(array $filters, $user)
    {
        if ($user->cannot('readAny', Animal::class)) {
            throw new AuthorizationException('No tiene permisos para listar animales.');
        }

        $query = Animal::with([
            'rebano.finca.propietario.persona',
            'composicionRaza',
            'etapaActual.etapa',
            'estadoActual.estadoSalud'
        ]);

        $incluirArchivados = filter_var($filters['incluir_archivados'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$incluirArchivados) {
            $archivado = filter_var($filters['archivado'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $query->where('archivado', $archivado);
        }

        // Aplicamos los filtros básicos si existen en la petición
        if (!empty($filters['finca_id'])) {
            $query->forFinca($filters['finca_id']);
        }

        if (!empty($filters['rebano_id'])) {
            $query->forRebano($filters['rebano_id']);
        }

        if (!empty($filters['sexo'])) {
            $query->bySex($filters['sexo']);
        }

        $this->applyFincaFilter($query, $user, 'rebano');

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Guarda un nuevo animal y inicializa sus estados y etapas si se proveen.
     *
     * @param array $data Datos del animal y estados/etapas iniciales.
     * @param mixed $user Usuario que realiza la petición.
     * @return array Contiene el modelo del Animal creado y los metadatos de clasificación.
     * @throws AuthorizationException
     */
    public function storeAnimal(array $data, $user): array
    {
        // Validación de permisos
        if ($user->cannot('create', [Animal::class, (int)$data['rebano_id']])) {
            throw new AuthorizationException('No tiene permisos para agregar un animal a este rebaño.');
        }

        // Creación del animal mapeando los campos del request a las columnas actuales de la DB
        $animal = Animal::create([
            'rebano_id'           => $data['rebano_id'],
            'nombre'              => $data['nombre'] ?? null,
            'codigo_animal'       => $data['codigo_animal'] ?? null,
            'sexo'                => $data['sexo'],
            'fecha_nacimiento'    => $data['fecha_nacimiento'],
            'procedencia'         => $data['procedencia'] ?? null,
            'composicion_raza_id' => $data['composicion_raza_id'],
            'archivado'           => false
        ]);

        // Guarda el estado de salud inicial si fue enviado
        if (!empty($data['estado_inicial'])) {
            EstadoAnimal::create([
                'fecha_ini'       => $data['estado_inicial']['fecha_ini'],
                'fecha_fin'       => null,
                'estado_salud_id' => $data['estado_inicial']['estado_salud_id'],
                'animal_id'       => $animal->id,
            ]);
        }

        // Guarda la etapa inicial si fue enviada
        if (!empty($data['etapa_inicial'])) {
            EtapaAnimal::create([
                'fecha_ini' => $data['etapa_inicial']['fecha_ini'],
                'fecha_fin' => null,
                'animal_id' => $animal->id,
                'etapa_id'  => $data['etapa_inicial']['etapa_id'],
            ]);
        }

        // Ejecuta la clasificación y sincronización automática de etapa
        $lastWeight = PesoCorporal::where('animal_etapa_id', $animal->id)
            ->orderByDesc('fecha_peso')
            ->value('peso');

        $clasificacion = $this->etapaClassifier->syncCurrentEtapa($animal, $lastWeight !== null ? (float) $lastWeight : null);

        return [$animal, $clasificacion];
    }

    /**
     * Retorna los detalles de un animal específico validando permisos de acceso.
     *
     * @param int $id ID del animal.
     * @param mixed $user Usuario que realiza la petición.
     * @return Animal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getAnimal(int $id, $user): Animal
    {
        $animal = Animal::with([
            'rebano.finca.propietario.persona',
            'composicionRaza',
            'estadoActual.estadoSalud',
            'estados.estadoSalud',
            'etapaAnimales.etapa',
            'etapaActual.etapa'
        ])->findOrFail($id);

        if ($user->cannot('read', $animal)) {
            throw new AuthorizationException('No tiene permisos para ver este animal.');
        }

        return $animal;
    }

    /**
     * Actualiza los datos de un animal y recalcula su etapa de crecimiento.
     *
     * @param int $id ID del animal.
     * @param array $data Datos a actualizar.
     * @param mixed $user Usuario.
     * @return array Contiene el modelo actualizado y los metadatos de clasificación.
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateAnimal(int $id, array $data, $user): array
    {
        $animal = Animal::findOrFail($id);

        // Validación de permisos
        if ($user->cannot('update', $animal)) {
            throw new AuthorizationException('No tiene permisos para actualizar este animal.');
        }

        // Si intenta cambiar de rebaño, valida pertenencia en el nuevo rebaño
        if (!empty($data['rebano_id']) && $data['rebano_id'] != $animal->rebano_id) {
            if ($user->cannot('create', [Animal::class, (int)$data['rebano_id']])) {
                throw new AuthorizationException('No tiene permisos para mover el animal a ese rebaño.');
            }
        }

        // Mapeo selectivo de campos para la actualización
        $updatePayload = [];
        if (array_key_exists('rebano_id', $data)) $updatePayload['rebano_id'] = $data['rebano_id'];
        if (array_key_exists('nombre', $data)) $updatePayload['nombre'] = $data['nombre'];
        if (array_key_exists('codigo_animal', $data)) $updatePayload['codigo_animal'] = $data['codigo_animal'];
        if (array_key_exists('sexo', $data)) $updatePayload['sexo'] = $data['sexo'];
        if (array_key_exists('fecha_nacimiento', $data)) $updatePayload['fecha_nacimiento'] = $data['fecha_nacimiento'];
        if (array_key_exists('procedencia', $data)) $updatePayload['procedencia'] = $data['procedencia'];
        if (array_key_exists('composicion_raza_id', $data)) $updatePayload['composicion_raza_id'] = $data['composicion_raza_id'];

        $animal->update($updatePayload);

        // Recalculamos la clasificación tras la actualización
        $lastWeight = PesoCorporal::where('animal_etapa_id', $animal->id)
            ->orderByDesc('fecha_peso')
            ->value('peso');

        $clasificacion = $this->etapaClassifier->syncCurrentEtapa($animal, $lastWeight !== null ? (float) $lastWeight : null);

        return [$animal, $clasificacion];
    }

    /**
     * Realiza un archivado (archivado = true) del animal.
     *
     * @param int $id ID del animal.
     * @param mixed $user Usuario.
     * @return Animal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function archiveAnimal(int $id, $user): Animal
    {
        $animal = Animal::findOrFail($id);

        if ($user->cannot('update', $animal)) {
            throw new AuthorizationException('No tiene permisos para archivar este animal.');
        }

        $animal->update(['archivado' => true]);

        return $animal->fresh([
            'rebano.finca.propietario.persona',
            'composicionRaza',
            'etapaActual.etapa',
            'estadoActual.estadoSalud'
        ]);
    }

    /**
     * Desarchiva un animal archivado (archivado = false).
     * Si su rebaño o finca padre estaban archivados, los reactiva automáticamente.
     *
     * @param int $id ID del animal.
     * @param mixed $user Usuario que realiza la acción.
     * @return Animal
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function unarchiveAnimal(int $id, $user): Animal
    {
        $animal = Animal::with('rebano.finca')->findOrFail($id);

        if ($user->cannot('update', $animal)) {
            throw new AuthorizationException('No tiene permisos para desarchivar este animal.');
        }

        return DB::transaction(function () use ($animal) {
            $animal->update(['archivado' => false]);

            // Reactivar rebaño si estaba archivado
            if ($animal->rebano && $animal->rebano->archivado) {
                $animal->rebano->update(['archivado' => false]);
            }

            // Reactivar finca si estaba archivada
            if ($animal->rebano && $animal->rebano->finca && $animal->rebano->finca->archivado) {
                $animal->rebano->finca->update(['archivado' => false]);
            }

            return $animal->fresh([
                'rebano.finca.propietario.persona',
                'composicionRaza',
                'etapaActual.etapa',
                'estadoActual.estadoSalud'
            ]);
        });
    }

    /**
     * Elimina físicamente un animal y sus registros dependientes en cascada.
     *
     * @param int $id ID del animal.
     * @param mixed $user Usuario.
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function deleteAnimal(int $id, $user): bool
    {
        $animal = Animal::findOrFail($id);

        if ($user->cannot('delete', $animal)) {
            throw new AuthorizationException('No tiene permisos para eliminar este animal.');
        }

        return DB::transaction(function () use ($animal) {
            $animal->delete();
            return true;
        });
    }
}
