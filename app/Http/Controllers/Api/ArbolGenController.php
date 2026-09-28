<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Services\Animal\ArbolGenService;
use App\Http\Resources\Animal\ArbolGenResource;
use App\Http\Resources\Animal\AnimalResource;
use App\Http\Middleware\Legacy\Animal\ArbolGen\NormalizeGetTree;
use App\Http\Middleware\Legacy\Animal\ArbolGen\NormalizeSetParent;
use App\Http\Middleware\Legacy\Animal\ArbolGen\NormalizeGetAvailableParents;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ArbolGenController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta los servicios y registra los middlewares de normalización legacy.
     */
    public function __construct(
        protected ArbolGenService $arbolGenService
    ) {
        $this->middleware(NormalizeGetTree::class)->only('getTree');
        $this->middleware(NormalizeSetParent::class)->only('setParent');
        $this->middleware(NormalizeGetAvailableParents::class)->only('getAvailableParents');
    }

    /**
     * Devuelve el árbol genealógico completo de un animal (3 generaciones).
     */
    public function getTree(Request $request, Animal $animal)
    {
        try {
            $treeData = $this->arbolGenService->showTree($animal, $request->user());
            return response()->json([
                'success' => true,
                'data' => $this->formatResource(ArbolGenResource::class, $treeData)
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Registra o actualiza la relación padre/madre de un animal.
     * Body: { tipo: 'Padre'|'Madre', id_padre: int }
     */
    public function setParent(Request $request, Animal $animal)
    {
        $validator = Validator::make($request->all(), [
            'tipo'     => 'required|in:Padre,Madre',
            'padre_id' => 'required|integer|exists:animals,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $relacion = $this->arbolGenService->store($animal, $request->tipo, (int) $request->padre_id, $request->user());

            return response()->json([
                'success' => true,
                'message' => "Relación de {$request->tipo} guardada correctamente.",
                'data'    => $relacion,
            ], Response::HTTP_OK);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Elimina la relación padre o madre de un animal.
     * Route param: tipo = 'Padre'|'Madre'
     */
    public function removeParent(Request $request, Animal $animal, string $tipo)
    {
        if (!in_array($tipo, ['Padre', 'Madre'])) {
            return response()->json(['success' => false, 'message' => 'Tipo inválido. Use Padre o Madre.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $deleted = $this->arbolGenService->destroy($animal, $tipo, $request->user());

            if (!$deleted) {
                return response()->json(['success' => false, 'message' => 'No se encontró la relación a eliminar.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['success' => true, 'message' => "Relación de {$tipo} eliminada."]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Lista animales disponibles para asignar como progenitor.
     */
    public function getAvailableParents(Request $request, Animal $animal)
    {
        try {
            $tipo = $request->query('tipo');
            $animales = $this->arbolGenService->getAvailableParents($animal, $tipo, $request->user());

            return response()->json([
                'success' => true,
                'data' => $this->formatCollection(AnimalResource::class, $animales)
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
