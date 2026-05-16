<?php

namespace App\Routes;

use Vatts\Router\Router;
use Vatts\Router\Request;
use Vatts\Router\Response;
use App\controllers\Api\admin\ServersController;
use App\controllers\Api\admin\UsersController;
use App\controllers\Api\admin\NodesController;

class AdminRoutes
{
    public static function setup(Router $router)
    {
        $router->group(["prefix" => '/v1/admin', 'middleware' => 'token'], function (Router $router) {

            $router->get('/', function (Request $request, Response $response) {
                return $response->json(['success' => true]);
            });

            // Rotas de Servidores
            $router->group(["prefix" => '/servers'], function (Router $router) {
                $router->get('/', [ServersController::class, 'list']);
                $router->post('/create', [ServersController::class, 'create']);
                $router->post('/delete', [ServersController::class, 'delete']);
                $router->post('/edit', [ServersController::class, 'edit']);
            });

            // Rotas de Usuários
            $router->group(["prefix" => '/users'], function (Router $router) {
                $router->get('/', [UsersController::class, 'get']);
                $router->post('/create', [UsersController::class, 'create']);
                $router->post('/delete', [UsersController::class, 'delete']);
                $router->post('/edit', [UsersController::class, 'edit']);
            });

            // Rotas de Nodes
            $router->group(['prefix' => '/nodes'], function (Router $router) {
                $router->get('/online', [NodesController::class, 'getOnlineNodesByLocation']);
                $router->post('/[nodeId]/allocations', [NodesController::class, 'getFreeAllocationsByNode']);
                $router->post('/status', [NodesController::class, 'getNodeStatus']);
            });
        });
    }
}