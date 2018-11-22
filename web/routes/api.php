<?php

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

Route::post('device', 'ApiController@createDevice')->name('device.create');
Route::delete('device/{id}', 'ApiController@deleteDevice')->name('device.create');
Route::get('device', 'ApiController@getDevices')->name('devices.get');
Route::get('trait-types', 'ApiController@getTraitTypes')->name('trait.types.get');
Route::get('device-types', 'ApiController@getDeviceTypes')->name('device.types.get');