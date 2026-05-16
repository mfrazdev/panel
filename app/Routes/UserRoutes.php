<?php

namespace App\Routes;

use App\controllers\Api\Server\AllocationController;
use App\controllers\Api\Server\ServerController;
use App\controllers\Api\Server\ServerDatabasesApiController;
use App\controllers\Api\Server\ServerSchedulersApiController;
use App\controllers\Api\Server\ServerStartupApiController;
use App\controllers\Api\Users\UserController;
use Vatts\Router\Router;

class UserRoutes
{
    public static function setup(Router $router): void
    {
        $router->group(["prefix" => '/v1/users', 'middleware' => 'api'], function (Router $router) {
            $router->get('/servers', [UserController::class, 'getServers']);
            $router->post('/email', [UserController::class, 'changeEmail']);

            // Rotas de Gerenciamento do Servidor
            $router->group(["prefix" => '/server', "middleware" => 'server'], function (Router $router) {

                // Base
                $router->get('/[server_id]', [ServerController::class, 'getServer']);
                $router->get('/[server_id]/status', [ServerController::class, 'getStatus']);
                $router->get('/[server_id]/audit', [ServerController::class, 'getAudit']);
                $router->post('/[server_id]/config', [ServerController::class, 'saveNameAndDesc']);

                // Actions
                $router->post('/[server_id]/action', [ServerController::class, 'sendAction']);
                $router->get('/[server_id]/action/[action]', [ServerController::class, 'sendAction']);

                // Startup
                $router->get('/[server_id]/startup', [ServerStartupApiController::class, 'getCoreInfo']);
                $router->post('/[server_id]/startup/docker', [ServerStartupApiController::class, 'saveDockerImage']);
                $router->post('/[server_id]/startup/variable', [ServerStartupApiController::class, 'saveVariable']);

                // Alocações (Portas/IPs)
                $router->get('/[server_id]/allocations', [AllocationController::class, 'getAdditionalAllocations']);
                $router->post('/[server_id]/allocations/add', [AllocationController::class, 'addAdditionalAllocation']);
                $router->post('/[server_id]/allocations/remove', [AllocationController::class, 'removeAdditionalAllocation']);

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