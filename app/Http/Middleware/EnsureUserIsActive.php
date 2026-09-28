<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Revisa si el usuario autenticado está activo.
     * Si está suspendido o inactivo, bloquea el acceso a rutas operativas con HTTP 403.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && in_array(strtolower($user->status), ['suspended', 'inactive', 'suspendido', 'inactivo'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta se encuentra suspendida. Contacta a un administrador.'
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
