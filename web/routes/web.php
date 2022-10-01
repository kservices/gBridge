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

use App\Http\Controllers\AccesskeyController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\GapiController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;
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
Route::resource('device', DeviceController::class)->only('store', 'index', 'create', 'update', 'destroy', 'edit');
Route::put('/device/{device}/updatetopic/{trait}', [DeviceController::class, 'updatetopic'])->name('device.updatetopic')->middleware('auth');
//Route::get('/temp-syncdev', [DeviceController::class, 'allUserInfoToCache'])->middleware('auth');

//Accesskey-management
Route::resource('accesskey', AccesskeyController::class)->only('index', 'destroy');

//User profile management
Route::get('profile', [UserProfileController::class, 'index'])->name('profile.index')->middleware('auth');
Route::post('profile/updatepwd', [UserProfileController::class, 'updatepwd'])->name('profile.updatepwd')->middleware('auth');
Route::post('profile/updatemqtt', [UserProfileController::class, 'updatemqtt'])->name('profile.updatemqtt')->middleware('auth');
Route::post('profile/updatename', [UserProfileController::class, 'updatename'])->name('profile.updatename')->middleware('auth');
Route::post('profile/updatelang', [UserProfileController::class, 'updatelang'])->name('profile.updatelang')->middleware('auth');
Route::get('profile/verify/{verify_token}', [UserProfileController::class, 'verify'])->name('profile.verify');                   //auth is not necessary for verifying the account

//Google Actions api
Route::get('gapi/auth', [GapiController::class, 'auth'])->name('gapi.auth');
Route::post('gapi/auth', [GapiController::class, 'checkauth'])->name('gapi.checkauth');
Route::any('gapi', [GapiController::class, 'apicall'])->name('gapi.apicall');

//API v2 key management
Route::get('apikey', [ApiKeyController::class, 'index'])->name('apikey.index')->middleware('auth');
Route::get('apikey/create/standard', [ApiKeyController::class, 'createStandardKey'])->name('apikey.createStandard')->middleware('auth');
Route::get('apikey/create/user', [ApiKeyController::class, 'createUserKey'])->name('apikey.createUser')->middleware('auth');
Route::delete('apikey/{apikey_id}/delete', [ApiKeyController::class, 'destroy'])->name('apikey.destroy')->middleware('auth');

//Disable user registration
if (env('DISABLE_REGISTRATION', false)) {
    Route::any('register', function () {
        abort(404);
    });
}
