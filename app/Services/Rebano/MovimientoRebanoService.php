<?php

namespace App\Services\Rebano;

use App\Models\MovimientoRebano;
use App\Models\MovimientoRebanoAnimal;
use App\Models\Animal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Auth\Access\AuthorizationException;

use App\Services\BaseService;

class MovimientoRebanoService extends BaseService
{
    /**
     * Obtiene la lista de movimientos de rebaño con filtros.
     *
     * @param array $filters Filtros.
     * @param mixed $user Usuario.
     * @return LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function listMovimientos(array $filters, $user)
    {

        if ($user->cannot('readAny', MovimientoRebano::class)) {
            throw new AuthorizationException('Sin permisos para listar los movimientos de rebaño.');
        }

        $query = MovimientoRebano::with([
            'fincaOrigen', 'rebanoOrigen',
            'fincaDestino', 'rebanoDestino',
            'animales.animal'
        ]);

        if (!empty($filters['finca_id'])) {
            $query->forFinca($filters['finca_id']);
        }

        if (!empty($filters['rebano_id'])) {
            $query->forRebano($filters['rebano_id']);
        }

        $this->applyFincaFilter($query, $user, 'fincaOrigen');

        if (!empty($filters['nopaginate'])) {
            return $query->get();
        }

        return $query->paginate(15);
    }

    /**
     * Registra un nuevo movimiento de rebaño y actualiza la ubicación de los animales en una transacción.
     *
     * @param array $data Datos.
     * @param mixed $user Usuario.
     * @return MovimientoRebano
     * @throws UnprocessableEntityHttpException
     * @throws AuthorizationException
     */
    public function createMovimiento(array $data, $user): MovimientoRebano
    {

        if ($user->cannot('create', [MovimientoRebano::class, (int) $data['finca_id'], (int) $data['finca_destino_id']])) {
            throw new AuthorizationException('No tiene permisos para crear un movimiento entre estas fincas.');
        }
        if ((int) $data['rebano_id'] === (int) $data['rebano_destino_id']) {
            throw new UnprocessableEntityHttpException('El rebaño de destino debe ser diferente al de origen.');
        }

        $animalIds = collect($data['animales'])->map(fn ($id) => (int) $id)->unique()->values()->all();
        
        // Validar que todos los animales pertenezcan al rebaño de origen
        $animalesOrigenCount = Animal::whereIn('id', $animalIds)
            ->where('rebano_id', (int) $data['rebano_id'])
            ->count();

        if ($animalesOrigenCount !== count($animalIds)) {
            throw new UnprocessableEntityHttpException('Todos los animales seleccionados deben pertenecer al rebaño de origen.');
        }

        return DB::transaction(function () use ($data, $animalIds) {
            $mov = MovimientoRebano::create([
                'finca_id'          => $data['finca_id'],
                'rebano_id'         => $data['rebano_id'],
                'rebano_destino'    => $data['rebano_destino'] ?? null,
                'finca_destino_id'  => $data['finca_destino_id'],
                'rebano_destino_id' => $data['rebano_destino_id'],
                'comentario'        => $data['comentario'] ?? null,
            ]);

            foreach ($animalIds as $animalId) {
                MovimientoRebanoAnimal::create([
                    'animal_id'            => $animalId,
                    'movimiento_rebano_id' => $mov->id,
                    'estado'               => 'activo',
                ]);
            }

            // Actualizar la relación de rebaño del animal en V2
            Animal::whereIn('id', $animalIds)
                ->update(['rebano_id' => (int) $data['rebano_destino_id']]);

            return $mov;
        });
    }

    /**
     * Obtiene el detalle de un movimiento específico.
     *
     * @param int $id ID.
     * @param mixed $user Usuario.
     * @return MovimientoRebano
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function getMovimientoById(int $id, $user): MovimientoRebano
    {
        $movimiento = MovimientoRebano::with([
            'fincaOrigen', 'rebanoOrigen',
            'fincaDestino', 'rebanoDestino',
            'animales.animal'
        ])->findOrFail($id);

        if ($user->cannot('read', $movimiento)) {
            throw new AuthorizationException('No tiene permisos para ver este movimiento.');
        }

        return $movimiento;
    }

    /**
     * Actualiza un movimiento de rebaño.
     *
     * @param int $id ID.
     * @param array $data Datos.
     * @param mixed $user Usuario.
     * @return MovimientoRebano
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function updateMovimiento(int $id, array $data, $user): MovimientoRebano
    {
        $movimiento = MovimientoRebano::findOrFail($id);

        if ($user->cannot('update', $movimiento)) {
            throw new AuthorizationException('No tiene permisos para actualizar este movimiento.');
        }

        $payload = [];
        if (array_key_exists('rebano_destino', $data)) $payload['rebano_destino'] = $data['rebano_destino'];
        if (array_key_exists('comentario', $data)) $payload['comentario'] = $data['comentario'];

        $movimiento->update($payload);

        return $movimiento;
    }

    /**
     * Elimina un movimiento de rebaño.
     *
     * @param int $id ID.
     * @param mixed $user Usuario.
     * @return bool
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function deleteMovimiento(int $id, $user): bool
    {
        $movimiento = MovimientoRebano::findOrFail($id);

        if ($user->cannot('delete', $movimiento)) {
            throw new AuthorizationException('No tiene permisos para eliminar este movimiento.');
        }

        DB::transaction(function () use ($movimiento) {
            // Obtener los IDs de los animales que participaron en este movimiento
            $animalIds = MovimientoRebanoAnimal::where('movimiento_rebano_id', $movimiento->id)
                ->pluck('animal_id')
                ->all();

            // Revertir los animales al rebaño de origen
            if (!empty($animalIds)) {
                Animal::whereIn('id', $animalIds)
                    ->update(['rebano_id' => $movimiento->rebano_id]);
            }

            // Eliminar los registros pivot y el movimiento
            MovimientoRebanoAnimal::where('movimiento_rebano_id', $movimiento->id)->delete();
            $movimiento->delete();
        });

        return true;
    }
}
