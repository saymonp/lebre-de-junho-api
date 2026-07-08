<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'getProfile']);
    Route::put('/user', [UserController::class, 'update']);
    // Sem nenhum "{user}" na URL. O Sanctum já sabe quem é pelo Token!
    Route::delete('/user', [UserController::class, 'deleteOwnAccount']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/users/{user}/roles', [UserController::class, 'assignRole']);
    Route::post('/users/{user}/permissions', [UserController::class, 'assignPermissions']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::get('/roles', [UserController::class, 'listRoles']);
    Route::get('/users', [UserController::class, 'index']);
});


Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout']);
Route::post('/verify/email', [UserController::class, 'verifyEmail']);
Route::post('/auth/google/callback', [UserController::class, 'handleGoogleCallback']);
Route::post('/recover-password', [UserController::class, 'recoverPassword']);
Route::post('/reset-password', [UserController::class, 'resetPassword']);
