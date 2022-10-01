<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Device;

Auth::routes();

if (env('KSERVICES_HOSTED', false)) {
    Route::get('/', function () {
        return redirect('https://about.gbridge.io');
    });
} else {
    Route::get('/', function () {
        return redirect('device');
    });
}
Route::get('/home', function () {
    return redirect('device');
});

//Device-Management panel
Route::resource('device', 'DeviceController')->only('store', 'index', 'create', 'update', 'destroy', 'edit');
Route::put('/device/{device}/updatetopic/{trait}', 'DeviceController@updatetopic')->name('device.updatetopic')->middleware('auth');
//Route::get('/temp-syncdev', 'DeviceController@allUserInfoToCache')->middleware('auth');

//Accesskey-management
Route::resource('accesskey', 'AccesskeyController')->only('index', 'destroy');

//User profile management
Route::get('profile', 'UserProfileController@index')->name('profile.index')->middleware('auth');
Route::post('profile/updatepwd', 'UserProfileController@updatepwd')->name('profile.updatepwd')->middleware('auth');
Route::post('profile/updatemqtt', 'UserProfileController@updatemqtt')->name('profile.updatemqtt')->middleware('auth');
Route::post('profile/updatename', 'UserProfileController@updatename')->name('profile.updatename')->middleware('auth');
Route::post('profile/updatelang', 'UserProfileController@updatelang')->name('profile.updatelang')->middleware('auth');
Route::get('profile/verify/{verify_token}', 'UserProfileController@verify')->name('profile.verify');                   //auth is not necessary for verifying the account

//Google Actions api
Route::get('gapi/auth', 'GapiController@auth')->name('gapi.auth');
Route::post('gapi/auth', 'GapiController@checkauth')->name('gapi.checkauth');
Route::any('gapi', 'GapiController@apicall')->name('gapi.apicall');

//API v2 key management
Route::get('apikey', 'ApiKeyController@index')->name('apikey.index')->middleware('auth');
Route::get('apikey/create/standard', 'ApiKeyController@createStandardKey')->name('apikey.createStandard')->middleware('auth');
Route::get('apikey/create/user', 'ApiKeyController@createUserKey')->name('apikey.createUser')->middleware('auth');
Route::delete('apikey/{apikey_id}/delete', 'ApiKeyController@destroy')->name('apikey.destroy')->middleware('auth');

//Disable user registration
if (env('DISABLE_REGISTRATION', false)) {
    Route::any('register', function () {
        abort(404);
    });
}
