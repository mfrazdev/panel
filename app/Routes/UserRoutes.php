<?php

namespace App\Routes;

use Vatts\Router\Router;
use App\controllers\Api\Users\UsersApiController;
use App\controllers\Api\Users\Servers\ServerStartupApiController;
use App\controllers\Api\Users\Servers\ServerSchedulersApiController;
use App\controllers\Api\Servers\ServerDatabasesApiController;

class UserRoutes
{
    public static function setup(Router $router): void
    {
        $router->group(["prefix" => '/v1/users', 'middleware' => 'api'], function (Router $router) {
            $router->get('/servers', [UsersApiController::class, 'getServers']);
            $router->post('/email', [UsersApiController::class, 'changeEmail']);

            // Rotas de Gerenciamento do Servidor
            $router->group(["prefix" => '/server', "middleware" => 'server'], function (Router $router) {

                // Base
                $router->get('/[server_id]', [UsersApiController::class, 'getServer']);
                $router->get('/[server_id]/status', [UsersApiController::class, 'getStatus']);
                $router->post('/[server_id]/config', [UsersApiController::class, 'saveNameAndDesc']);

                // Actions
                $router->post('/[server_id]/action', [UsersApiController::class, 'sendAction']);
                $router->get('/[server_id]/action/[action]', [UsersApiController::class, 'sendAction']);

                // Startup
                $router->get('/[server_id]/startup', [ServerStartupApiController::class, 'getCoreInfo']);
                $router->post('/[server_id]/startup/docker', [ServerStartupApiController::class, 'saveDockerImage']);
                $router->post('/[server_id]/startup/variable', [ServerStartupApiController::class, 'saveVariable']);

                // Alocações (Portas/IPs)
                $router->get('/[server_id]/allocations', [UsersApiController::class, 'getAdditionalAllocations']);
                $router->post('/[server_id]/allocations/add', [UsersApiController::class, 'addAdditionalAllocation']);
                $router->post('/[server_id]/allocations/remove', [UsersApiController::class, 'removeAdditionalAllocation']);

                // Banco de Dados
                $router->get('/[server_id]/databases', [ServerDatabasesApiController::class, 'getDatabases']);
                $router->post('/[server_id]/databases/create', [ServerDatabasesApiController::class, 'createDatabase']);
                $router->post('/[server_id]/databases/remove', [ServerDatabasesApiController::class, 'deleteDatabase']);

                // Tarefas Agendadas (Schedulers)
                $router->get('/[server_id]/schedulers', [ServerSchedulersApiController::class, 'list']);
                $router->post('/[server_id]/schedulers/create', [ServerSchedulersApiController::class, 'create']);
                $router->post('/[server_id]/schedulers/delete', [ServerSchedulersApiController::class, 'delete']);
                $router->post('/[server_id]/schedulers/toggle', [ServerSchedulersApiController::class, 'toggle']);
                $router->post('/[server_id]/schedulers/edit', [ServerSchedulersApiController::class, 'edit']);
            });
        });
    }
}