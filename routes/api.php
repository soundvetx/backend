<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Middleware\EnsureParametersCase;
use App\Services\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureParametersCase::class)->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/sign-up', [AuthenticationController::class, 'signUp']);
        Route::post('/sign-in', [AuthenticationController::class, 'signIn']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/sign-out', [AuthenticationController::class, 'signOut']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('users')->group(function () {
            Route::post('/', [UserController::class, 'create']);
            Route::put('/{idUser}', [UserController::class, 'update']);
            Route::delete('/{idUser}', [UserController::class, 'delete']);
            Route::patch('/{idUser}/restore', [UserController::class, 'restore']);
            Route::patch('/{idUser}/can-send-whatsapp', [UserController::class, 'canSendWhatsapp']);
            Route::patch('/{idUser}/change-password', [UserController::class, 'changePassword']);
            Route::patch('/{idUser}/reset-password', [UserController::class, 'resetPassword']);
        });
    });
});
