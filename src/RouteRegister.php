<?php
namespace Pooyadch\LaravelMapService;

use Illuminate\Contracts\Routing\Registrar as Router;

class RouteRegister
{
    /**
     * The router implementation.
     *
     * @var \Illuminate\Contracts\Routing\Registrar
     */
    protected $router;

    /**
     * Create a new route registrar instance.
     *
     * @param  \Illuminate\Contracts\Routing\Registrar $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Register routes for transient tokens, clients, and personal access tokens.
     *
     * @return void
     */
    public function all()
    {
        $this->forCustomMap();
    }



    public function forCustomMap()
    {

        $this->router->group([
            'prefix' => '/v1/custom/pooyadch/',
        ], function (Router $router) {
            $router->get(
                '/map/search',
                'LaravelMapSearchAddressController@searchAddress'
            );
            $router->get(
                '/map/find',
                'LaravelMapFindAddressController@findAddress');
        });
    }
}