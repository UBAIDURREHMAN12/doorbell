<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/register', 'ApiController@register');
Route::post('/login', 'ApiController@login');

Route::post('/reset/password', 'ApiController@ResetPassword');
Route::post('/code/confirmation', 'ApiController@codeVerification');
Route::post('/account/confirmation/code', 'ApiController@AccountConfirmation');
Route::post('/update/password', 'ApiController@updatePassword');

Route::middleware('auth:api')->group( function () {

    Route::post('/create/door', 'ApiController@createDoor');

    Route::get('/fetch/users', 'ApiController@FetchUsers');
    Route::get('/fetch/user/{id}', 'ApiController@FetchUser');
    Route::post('/update/profile/{id}', 'ApiController@UpdateProfile');
    Route::post('/update/listed/staus', 'ApiController@UpdateListedStatus');
    Route::post('/update/notify/staus', 'ApiController@UpdateNotifyStatus');
    Route::post('/update/door/type/staus', 'ApiController@UpdatePublicStatus');
    Route::get('/user/doors/{id}', 'ApiController@FetchDoorsForSingleUser');
    Route::get('/delete/door/{id}', 'ApiController@DeleteDoor');
    Route::get('/get/door/details/{id}', 'ApiController@GetSingleDoorData');
    Route::post('/add/host', 'ApiController@AddHost');
    Route::post('/remove/host', 'ApiController@RemoveHost');
    Route::put('/update/door', 'ApiController@UpdateDoor');
    Route::post('update/door/hours/messages', 'ApiController@UpdateDoorMessages');
    Route::post('send/notification', 'ApiController@SendNotification');
    Route::get('get/notifications', 'ApiController@GetNotifications');
    Route::post('update/fcm', 'ApiController@UpdateFcm');

    Route::get('/test', 'ApiController@test');
    Route::post('/logout', 'ApiController@logout');
});
