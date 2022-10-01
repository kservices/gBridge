<?php

/*
|--------------------------------------------------------------------------
| API Routes (V2)
| Prefix: api/v2
|--------------------------------------------------------------------------
*/

Route::middleware('api')->prefix('auth')->group(function ($router) {
    Route::post('token', 'ApiV2\ApiV2AuthController@token');
    Route::post('logout', 'ApiV2\ApiV2AuthController@logout');
    //Route::post('refresh', 'ApiV2\ApiV2AuthController@refresh'); //->refresh disabled for the moment
});

Route::get('requestsync', 'ApiV2\ApiV2@requestSynchronization');

Route::get('device', 'ApiV2\ApiV2@getDevices');
Route::post('device', 'ApiV2\ApiV2@createDevice');
Route::get('device/{device}', 'ApiV2\ApiV2@getDeviceById');
Route::patch('device/{device}', 'ApiV2\ApiV2@updateDeviceById');
Route::delete('device/{device}', 'ApiV2\ApiV2@deleteDeviceById');

Route::get('user', 'ApiV2\ApiV2@getUserDetails');
Route::post('user/password', 'ApiV2\ApiV2@updateUserPassword');
Route::post('user/mqtt/password', 'ApiV2\ApiV2@updateUserMqttPassword');
