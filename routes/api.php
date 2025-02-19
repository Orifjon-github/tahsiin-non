<?php

use App\Http\Controllers\AppealTypeController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\TelegramBotController;
use App\Http\Controllers\TelegramTextController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/start', [TelegramBotController::class, 'start'])->middleware('elk');
Route::get('/ping', [MainController::class, 'ping']);

Route::controller(MainController::class)
    ->middleware(['auth', 'elk'])
    ->group(function () {
        Route::prefix('chat')->group(function () {
            Route::get('opens', 'openChats');
            Route::get('all', 'all');
            Route::post('detail/{id}', 'chatDetail');
            Route::post('admin', 'adminChats');
            Route::post('activate', 'activate');
            Route::post('close', 'close');
            Route::post('metric', 'metric');
            Route::post('send/message', 'sendMessage');
        });

        Route::apiResource('telegram-texts', TelegramTextController::class)->except(['store', 'edit', 'create', 'destroy']);
        Route::apiResource('appeal-types', AppealTypeController::class);
        Route::apiResource('consultations', ConsultationController::class);
    });


Route::fallback(function () {
    return response()->json([
        'message' => 'Route not found!',
    ]);
});
