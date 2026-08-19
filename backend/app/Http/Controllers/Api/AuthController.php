<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'Credenciales inválidas. Verifica tu correo y contraseña.'
            ], 401);
        }

        // Crear token simple para la API
        $token = hash('sha256', $user->id . $user->email . now() . 'refuguia_secret_salt');

        return response()->json([
            'success' => true,
            'message' => 'Sesión iniciada correctamente en RefuGuía.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'role_label' => match($user->role) {
                    'shelter_admin' => 'Administradora de Refugio',
                    'rescuer' => 'Rescatista de Campo',
                    'citizen' => 'Ciudadano / Damnificado',
                    'adopter' => 'Adoptante Responsable',
                    default => 'Usuario'
                },
                'location_zone' => $user->location_zone,
                'trust_score' => $user->trust_score
            ]
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Sesión activa.',
            'test_accounts' => [
                [
                    'role' => 'shelter_admin',
                    'label' => 'Administradora de Refugio (Dra. Carmen)',
                    'email' => 'carmen.refugio@refuguia.org',
                    'password' => 'Password123!',
                    'permissions' => 'Fichas clínicas, impresión de collares QR, dispensación de fármacos, adopciones.'
                ],
                [
                    'role' => 'rescuer',
                    'label' => 'Rescatista de Campo (Carlos)',
                    'email' => 'carlos.rescate@refuguia.org',
                    'password' => 'Password123!',
                    'permissions' => 'Ingreso de mascotas en campamentos y escaneo en campo.'
                ],
                [
                    'role' => 'citizen',
                    'label' => 'Ciudadana Damnificada (María)',
                    'email' => 'maria.f@gmail.com',
                    'password' => 'Password123!',
                    'permissions' => 'Reporte de pérdida y confirmación de reencuentro en Matchmaker.'
                ],
                [
                    'role' => 'adopter',
                    'label' => 'Adoptante Responsable (Andrés)',
                    'email' => 'andres.m@gmail.com',
                    'password' => 'Password123!',
                    'permissions' => 'Postulación para adopción con evaluación de IA.'
                ]
            ]
        ]);
    }

    public function logout()
    {
        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente.'
        ]);
    }
}
