<?php

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

Route::post('login', 'UserController@login');
Route::group(['middleware' => 'auth:api'], function (){
    Route::apiResources([
        'users' => 'UserController',
    ]);
    Route::post('logout', 'UserController@logout');
});
