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

Route::namespace('Pooyadch\LaravelMapService\Http\Controllers')
    ->prefix('api')
    ->group(function () {
    Route::prefix('v1')->group(function () {
        Route::prefix('custom')->group(function () {
            Route::prefix('pooyadch')->group(function () {
                Route::prefix('map')->group(function () {
                    Route::get('find', 'LaravelMapFindAddressController@findAddress');
                    Route::get('search', 'LaravelMapSearchAddressController@searchAddress');
                });
            });
        });
    });
});

