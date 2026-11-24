<?php

use App\Http\Controllers\Api\V1\AppBalanceController;
use App\Http\Controllers\Api\V1\AppCoinController;
use App\Http\Controllers\Api\V1\AppDeviceController;
use App\Http\Controllers\Api\V1\AppGoalController;
use App\Http\Controllers\Api\V1\AppUnpairController;
use App\Http\Controllers\Api\V1\AppWithdrawalController;
use App\Http\Controllers\Api\V1\DeviceCoinController;
use App\Http\Controllers\Api\V1\DeviceConfigController;
use App\Http\Controllers\Api\V1\DeviceHeartbeatController;
use App\Http\Controllers\Api\V1\DeviceRegisterController;
use App\Http\Controllers\Api\V1\PairingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Device API Routes (auth via device API key)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:device', 'throttle:60,1'])->prefix('v1/device')->group(function () {
    Route::post('coins', [DeviceCoinController::class, 'store']);
    Route::post('coins/batch', [DeviceCoinController::class, 'storeBatch']);
    Route::post('pairing-code', [PairingController::class, 'publishCode'])->middleware('throttle:5,1');
    Route::post('heartbeat', [DeviceHeartbeatController::class, 'store']);
    Route::get('config', [DeviceConfigController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Mobile App API Routes (auth via pairing token)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:app', 'throttle:60,1'])->prefix('v1/app')->group(function () {
    // Read endpoints
    Route::get('balance', [AppBalanceController::class, 'index']);
    Route::get('coins', [AppCoinController::class, 'index']);
    Route::get('goals', [AppGoalController::class, 'index']);
    Route::get('goals/{id}', [AppGoalController::class, 'show']);
    Route::get('device', [AppDeviceController::class, 'show']);
    Route::get('withdrawals', [AppWithdrawalController::class, 'index']);

    // Write endpoints — stricter rate limit
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('goals', [AppGoalController::class, 'store']);
        Route::put('goals/{id}', [AppGoalController::class, 'update']);
        Route::delete('goals/{id}', [AppGoalController::class, 'destroy']);
        Route::post('goals/{id}/reached', [AppGoalController::class, 'markReached']);
        Route::put('device', [AppDeviceController::class, 'update']);
        Route::post('withdrawals', [AppWithdrawalController::class, 'store'])->middleware('throttle:5,1');
        Route::delete('unpair', [AppUnpairController::class, 'destroy'])->middleware('throttle:3,1');
    });
});

/*
|--------------------------------------------------------------------------
| Pairing Routes (rate-limited, no auth)
|--------------------------------------------------------------------------
*/
Route::middleware('throttle:10,1')->prefix('v1')->group(function () {
    Route::post('pair', [PairingController::class, 'claim']);
    Route::post('device/register', [DeviceRegisterController::class, 'register'])->middleware('throttle:5,1');
});
