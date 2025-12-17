<?php

use App\Http\Controllers\AppealTypeController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\SupplierController;
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

Route::middleware('elk')->group(function () {
    Route::post('/start', [TelegramBotController::class, 'start']);
    Route::post('/send', [TelegramBotController::class, 'send']);
});

Route::get('/ping', [MainController::class, 'ping']);
Route::fallback(function () {
    return response()->json([
        'message' => 'Route not found!',
    ]);
});
