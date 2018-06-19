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

//redirect those to device, to default
Route::get('/', function () {
    return redirect('device');
});
Route::get('/home', function(){
    return redirect('device');
});

//Device-Management panel
Route::resource('device', 'DeviceController', [
    'only' => ['store', 'index', 'create', 'update', 'destroy', 'edit'],        //no show-method
]);

//Accesskey-management too
Route::resource('accesskey', 'AccesskeyController', [
    'only' => ['index', 'create', 'destroy']                            //limited functions
]);

//User profile management
Route::get('profile', 'UserProfileController@index')->name('profile.index')->middleware('auth');
Route::post('profile/updatepwd', 'UserProfileController@updatepwd')->name('profile.updatepwd')->middleware('auth');
Route::get('profile/verify/{verify_token}', 'UserProfileController@verify')->name('profile.verify');                   //auth is not necessary for verifying the account