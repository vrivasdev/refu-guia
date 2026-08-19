<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PetController;
use App\Http\Controllers\Api\ClinicalRecordController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\AdoptionController;
use App\Http\Controllers\Api\McpController;
use App\Http\Controllers\Api\SlmDiagnosticsController;

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'system' => 'RefuGuia Backend API',
        'timestamp' => now()->toIso8601String(),
        'slm_model' => 'qwen2.5:1.5b (Local Ollama)',
        'auth_support' => 'Enabled (Sanctum/Session RBAC)'
    ]);
});

// Autenticación y Sesiones
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/me', [AuthController::class, 'me']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

// Mascotas y Reportes Ciudadanos
Route::get('/pets', [PetController::class, 'index']);
Route::get('/pets/{id}', [PetController::class, 'show']);
Route::post('/pets/report-citizen', [PetController::class, 'processCitizenReport']);

// Expedientes Clínicos y QR
Route::post('/pets/{id}/clinical-records', [ClinicalRecordController::class, 'store']);

// Emparejamientos (Matches)
Route::get('/matches', [MatchController::class, 'index']);
Route::get('/matches/run/{petId}', [MatchController::class, 'runMatchForPet']);
Route::post('/matches/{id}/confirm', [MatchController::class, 'confirmMatch']);
Route::post('/matches/{id}/reject', [MatchController::class, 'rejectMatch']);

// Adopción Responsable
Route::get('/adoptions/adoptable-pets', [AdoptionController::class, 'getAdoptablePets']);
Route::post('/adoptions/evaluate', [AdoptionController::class, 'evaluateApplication']);

// MCP (Model Context Protocol) & Skills
Route::get('/mcp/tools', [McpController::class, 'getTools']);
Route::post('/mcp/invoke', [McpController::class, 'invokeTool']);
Route::get('/mcp/logs', [McpController::class, 'getLogs']);

// Diagnósticos de IA Local (Qwen 2.5:1.5b) & Seguridad
Route::get('/slm/health', [SlmDiagnosticsController::class, 'health']);
Route::post('/slm/inference', [SlmDiagnosticsController::class, 'testInference']);
Route::post('/slm/test-injection', [SlmDiagnosticsController::class, 'testPromptInjection']);
