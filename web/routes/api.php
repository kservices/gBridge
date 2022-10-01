<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('device', [ApiController::class, 'createDevice'])->name('device.create');
Route::delete('device/{id}', [ApiController::class, 'deleteDevice'])->name('device.create');
Route::get('device', [ApiController::class, 'getDevices'])->name('devices.get');
Route::get('trait-types', [ApiController::class, 'getTraitTypes'])->name('trait.types.get');
Route::get('device-types', [ApiController::class, 'getDeviceTypes'])->name('device.types.get');
