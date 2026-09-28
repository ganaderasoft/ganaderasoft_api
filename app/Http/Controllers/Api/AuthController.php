<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Http\Resources\User\UserResource;
use App\Http\Middleware\Legacy\User\NormalizeLogin;
use App\Http\Middleware\Legacy\User\NormalizeRegister;
use App\Http\Middleware\Legacy\User\NormalizeProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\User\AuthService;
use App\Services\User\ProfileService;
use Illuminate\Auth\Access\AuthorizationException;
use Exception;

class AuthController extends Controller
{
    /**
     * Constructor del controlador.
     * Inyecta los middlewares de compatibilidad para el front-end legacy y el servicio de perfil.
     */
    public function __construct(
        private ProfileService $profileService
    ) {
        $this->middleware(NormalizeLogin::class)->only('login');
        $this->middleware(NormalizeRegister::class)->only('register');
        $this->middleware(NormalizeProfile::class)->only('profile');
    }

    /**
     * Iniciar sesión del usuario y crear token
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();

        // NOTA: Se permite el login a cuentas suspendidas para que puedan autenticarse 
        // y consultar su perfil y estado de cuenta restringido en la interfaz.

        $user->load(['roles.permissions']);
        $token = $user->createToken('GanaderaSoft API Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'user' => $this->formatResource(UserResource::class, $user),
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * Obtener perfil del usuario autenticado
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load(['roles.permissions', 'personas', 'personas.propietario.persona']);
        
        return response()->json([
            'success' => true,
            'message' => 'Perfil de usuario',
            'data' => [
                'user' => $this->formatResource(UserResource::class, $user)
            ]
        ]);
    }

    /**
     * Cerrar sesión del usuario (revocar token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
    }

    /**
     * Registrar un nuevo usuario
     */
    public function register(Request $request, AuthService $authService)
    {
        $validator = Validator::make($request->all(), [
            'cedula' => 'required|string|max:20',
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'required|string|email|max:255|unique:users,email|unique:personas,correo',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|same:password',
            'role_code' => 'required|string|exists:roles,code',
            'fincas' => 'nullable|array',
            'fincas.*.id' => 'required_with:fincas|exists:fincas,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $user = $authService->registerUser($request->all(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => [
                    'user' => $this->formatResource(UserResource::class, $user)
                ]
            ], Response::HTTP_CREATED);

        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualiza la foto de perfil del usuario autenticado.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'foto.required' => 'Debe seleccionar una imagen para su foto de perfil.',
            'foto.image'    => 'El archivo seleccionado debe ser una imagen.',
            'foto.mimes'    => 'La imagen debe tener formato: jpeg, png, jpg o webp.',
            'foto.max'      => 'El tamaño máximo permitido para la imagen es de 5MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo proporcionado no es válido o supera el tamaño máximo permitido (5MB).',
                'errors'  => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $user = $this->profileService->updatePhoto($request->file('foto'), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Foto de perfil actualizada exitosamente.',
                'data'    => [
                    'user' => $this->formatResource(UserResource::class, $user),
                ],
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la foto de perfil: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Elimina la foto de perfil del usuario autenticado.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deletePhoto(Request $request)
    {
        try {
            $user = $this->profileService->deletePhoto($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Foto de perfil eliminada exitosamente.',
                'data'    => [
                    'user' => $this->formatResource(UserResource::class, $user),
                ],
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la foto de perfil: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}