<?php

namespace Pooyadch\LaravelMapService\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelMapService extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravelmapservice';
    }
}
