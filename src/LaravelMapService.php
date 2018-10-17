<?php

namespace Pooyadch\LaravelMapService;

use Illuminate\Support\Facades\Route;

class LaravelMapService
{
    public static function routes($callback = null, array $options = [])
    {

        $callback = $callback ?: function ($router) {
            $router->all();
        };

        $defaultOptions = [
            'prefix' => '/api',
            'namespace' => 'Pooyadch\LaravelMapService\Http\Controllers',
        ];

        $options = array_merge($defaultOptions, $options);
        Route::group($options, function ($router) use ($callback) {
            $callback(new RouteRegister($router));
        });
    }
}