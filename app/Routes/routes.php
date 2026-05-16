<?php

namespace App\Routes;

use Vatts\Router\Router;

class routes
{
    public static function setup(Router $router): void
    {
        // Carrega as rotas separadas por contexto
        SystemRoutes::setup($router);
        AdminRoutes::setup($router);
        UserRoutes::setup($router);
    }
}