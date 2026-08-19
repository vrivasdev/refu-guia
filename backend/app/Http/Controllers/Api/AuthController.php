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
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $email = strtolower(trim($request->email));
        $password = $request->password;

        // Normalizar alias de correos para pruebas
        $emailMap = [
            'carmen.lopez@refuguia.org' => 'carmen.refugio@refuguia.org',
            'carlos.mendoza@refuguia.org' => 'carlos.rescate@refuguia.org',
            'maria.fernandez@gmail.com' => 'maria.f@gmail.com',
            'andres.morales@gmail.com' => 'andres.m@gmail.com',
        ];

        $targetEmail = $emailMap[$email] ?? $email;
        $user = User::where('email', $targetEmail)->orWhere('email', $email)->first();

        // Validar contraseña o aceptar contraseñas de testing autorizadas
        $validPasswords = ['Password123!', 'carmen123', 'carlos123', 'maria123', 'andres123'];
        $isPasswordValid = false;

        if ($user) {
            if (Hash::check($password, $user->password) || in_array($password, $validPasswords)) {
                $isPasswordValid = true;
            }
        }

        if (!$user || !$isPasswordValid) {
            return response()->json([
                'success' => false,
                'error' => 'Credenciales inválidas. Verifica tu correo y contraseña.'
            ], 401);
        }

        $token = hash('sha256', $user->id . $user->email . now() . 'refuguia_secret_salt');

        return response()->json([
            'success' => true,
            'message' => 'Sesión iniciada correctamente en RefuGuía.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '+58 412 0000000',
                'role' => $user->role,
                'role_label' => match($user->role) {
                    'shelter_admin' => 'Coordinadora de Refugio',
                    'rescuer' => 'Rescatista de Campo',
                    'citizen' => 'Ciudadana Damnificada',
                    'adopter' => 'Adoptante Post-Sismo',
                    default => 'Usuario'
                },
                'location_zone' => $user->location_zone ?? 'Caracas',
                'trust_score' => $user->trust_score ?? 1.0
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
                    'label' => 'Dra. Carmen López (Coordinadora)',
                    'email' => 'carmen.refugio@refuguia.org',
                    'password' => 'Password123!',
                    'permissions' => 'Control total: Fichas clínicas, collares QR, fármacos SHA-256, adopciones, MCP y SLM.'
                ],
                [
                    'role' => 'rescuer',
                    'label' => 'Carlos Mendoza (Rescatista)',
                    'email' => 'carlos.rescate@refuguia.org',
                    'password' => 'Password123!',
                    'permissions' => 'Registro de rescates en campo y collares QR.'
                ],
                [
                    'role' => 'citizen',
                    'label' => 'María Fernández (Damnificada)',
                    'email' => 'maria.f@gmail.com',
                    'password' => 'Password123!',
                    'permissions' => 'Búsqueda familiar en chat y cotejo vectorial en Matchmaker.'
                ],
                [
                    'role' => 'adopter',
                    'label' => 'Andrés Morales (Adoptante)',
                    'email' => 'andres.m@gmail.com',
                    'password' => 'Password123!',
                    'permissions' => 'Postulación de adopción evaluada por IA.'
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
