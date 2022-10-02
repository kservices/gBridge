<?php

use App\Http\Controllers\ApiV2;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (V2)
| Prefix: api/v2
|--------------------------------------------------------------------------
*/

Route::middleware('api')->prefix('auth')->group(function ($router) {
    Route::post('token', [ApiV2\ApiV2AuthController::class, 'token']);
    Route::post('logout', [ApiV2\ApiV2AuthController::class, 'logout']);
    //Route::post('refresh', [ApiV2\ApiV2AuthController::class, 'refresh']); //->refresh disabled for the moment
});

Route::get('requestsync', [ApiV2\ApiV2::class, 'requestSynchronization']);

Route::get('device', [ApiV2\ApiV2::class, 'getDevices']);
Route::post('device', [ApiV2\ApiV2::class, 'createDevice']);
Route::get('device/{device}', [ApiV2\ApiV2::class, 'getDeviceById']);
Route::patch('device/{device}', [ApiV2\ApiV2::class, 'updateDeviceById']);
Route::delete('device/{device}', [ApiV2\ApiV2::class, 'deleteDeviceById']);

Route::get('user', [ApiV2\ApiV2::class, 'getUserDetails']);
Route::post('user/password', [ApiV2\ApiV2::class, 'updateUserPassword']);
Route::post('user/mqtt/password', [ApiV2\ApiV2::class, 'updateUserMqttPassword']);
