<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AddressController;

// 1. ROTAS PÚBLICAS (AuthController)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify/email', [AuthController::class, 'verifyEmail']);
Route::post('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
Route::post('/recover-password-request', [AuthController::class, 'recoverPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// 2. ROTAS DO CLIENTE LOGADO (ProfileController)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [ProfileController::class, 'getProfile']);
    Route::put('/user', [ProfileController::class, 'update']);
    Route::delete('/user', [ProfileController::class, 'deleteOwnAccount']);
    Route::post('/logout', [AuthController::class, 'logout']); // O logout usa a infra de auth
});

// 3. ROTAS ADMINISTRATIVAS (AdminUserController)
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users/{user}/roles', [AdminUserController::class, 'assignRole']);
    Route::post('/users/{user}/permissions', [AdminUserController::class, 'assignPermissions']);

    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])
        ->middleware('permission:delete users');

    // RoleController e PermissionController no futuro:
    Route::get('/roles', [AdminUserController::class, 'listRoles']);
    Route::get('/permissions', [AdminUserController::class, 'listPermissions']);
});

// ROTAS DE ENDEREÇO
Route::middleware(['auth:sanctum'])->prefix('addresses')->group(function () {
    Route::post('/', [AddressController::class, 'store']);
    Route::put('/{id}', [AddressController::class, 'update']);
    Route::delete('/{id}', [AddressController::class, 'destroy']);
    Route::get('/{id}', [AddressController::class, 'show']);
    Route::get('/', [AddressController::class, 'index']);
});
