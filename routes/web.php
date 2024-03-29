<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('add/host/sync/{id1}/{id2}/{id3}/{id4}', 'ApiController@AddGuest2');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
