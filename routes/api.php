<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\ExamRequestController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EnsureParametersCase;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureParametersCase::class)->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/sign-up', [AuthenticationController::class, 'signUp']);
        Route::post('/sign-in', [AuthenticationController::class, 'signIn']);
        Route::post('/forgot-password', [AuthenticationController::class, 'forgotPassword']);
        Route::patch('/reset-password', [AuthenticationController::class, 'resetPassword']);

        Route::middleware(Authenticate::class)->group(function () {
            Route::post('/sign-out', [AuthenticationController::class, 'signOut']);
        });
    });

    Route::middleware(Authenticate::class)->group(function () {
        Route::prefix('users')->group(function () {
            Route::get('/me', [UserController::class, 'findMe']);
            Route::get('/', [UserController::class, 'findAll']);
            Route::get('/{idUser}', [UserController::class, 'find']);
            Route::post('/', [UserController::class, 'create']);
            Route::put('/{idUser}', [UserController::class, 'update']);
            Route::delete('/{idUser}', [UserController::class, 'delete']);
            Route::patch('/{idUser}/restore', [UserController::class, 'restore']);
            Route::patch('/{idUser}/can-send-message', [UserController::class, 'canSendMessage']);
            Route::patch('/{idUser}/change-password', [UserController::class, 'changePassword']);
            Route::patch('/{idUser}/reset-password', [UserController::class, 'resetPassword']);
        });

        Route::prefix('exam-requests')->group(function () {
            Route::post('/generate', [ExamRequestController::class, 'generate']);
            Route::post('/send', [ExamRequestController::class, 'send']);
        });
    });
});
