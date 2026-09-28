<?php

use App\Http\Controllers\Api\ResidentAppController;
use App\Http\Controllers\Api\ResidentAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('app')->group(function () {
    Route::get('/health', fn () => response()->json(['ok' => true, 'service' => 'exclusivelocker-app']));

    Route::post('/auth/register', [ResidentAuthController::class, 'register']);
    Route::post('/auth/login', [ResidentAuthController::class, 'login']);
    Route::post('/auth/login-phone', [ResidentAuthController::class, 'loginByPhone']);
    Route::post('/auth/forgot', [ResidentAuthController::class, 'forgot']);
    Route::post('/auth/reset', [ResidentAuthController::class, 'reset']);
    Route::post('/auth/lookup-phone', [ResidentAuthController::class, 'lookupPhone']);

    Route::get('/conteudo/{chave}', [ResidentAppController::class, 'content']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [ResidentAuthController::class, 'me']);
        Route::post('/auth/logout', [ResidentAuthController::class, 'logout']);

        Route::get('/dashboard', [ResidentAppController::class, 'dashboard']);
        Route::post('/refresh', [ResidentAppController::class, 'refresh']);

        Route::get('/correspondencias/pendentes', [ResidentAppController::class, 'pendentes']);
        Route::get('/correspondencias/historico', [ResidentAppController::class, 'historico']);
        Route::get('/correspondencias/{id}', [ResidentAppController::class, 'showMovimento'])->whereNumber('id');

        Route::put('/perfil', [ResidentAppController::class, 'updateProfile']);
        Route::put('/notificacoes/preferencias', [ResidentAppController::class, 'updateNotifications']);
        Route::get('/notificacoes', [ResidentAppController::class, 'notifications']);
        Route::post('/notificacoes/ler-todas', [ResidentAppController::class, 'readAllNotifications']);
        Route::post('/notificacoes/{notification}/ler', [ResidentAppController::class, 'readNotification']);

        Route::get('/suporte', [ResidentAppController::class, 'myTickets']);
        Route::post('/suporte', [ResidentAppController::class, 'support']);
    });
});
