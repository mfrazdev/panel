<?php

namespace App\controllers\Api;

use App\controllers\Api\NodesHelper;
use App\controllers\Api\Users\Servers\ServerStartupApiController;
use App\controllers\Api\Users\UsersApiController;
use Vatts\Router\Request;
use Vatts\Router\Response;

class ApiRoutes
{
    public static function setup(\Vatts\Router\Router $router)
    {
        $router->group(["prefix" => "/nodes/helper"], function (\Vatts\Router\Router $router) {
            $router->post('/admin-permission', [NodesHelper::class, 'isAdmin']);
            $router->post('/permission', [NodesHelper::class, 'permission']);
            $router->post("/verify-sftp", [NodesHelper::class, 'verifysftp']);
        });
        $router->group(["prefix" => '/v1/admin', 'middleware' => 'token'], function (\Vatts\Router\Router $router) {
            $router->get('/', function (Request $request, Response $response) {
               return $response->json([
                   'success' => true,
               ]);
            });
            // Rotas de Servidores
            $router->group(["prefix" => '/servers'], function (\Vatts\Router\Router $router) {
                $router->get('/', [\App\controllers\Api\admin\ServersController::class, 'list']);
                $router->post('/create', [\App\controllers\Api\admin\ServersController::class, 'create']);
                $router->post('/delete', [\App\controllers\Api\admin\ServersController::class, 'delete']);
                $router->post('/edit', [\App\controllers\Api\admin\ServersController::class, 'edit']);
            });

            // Rotas de Usuários
            $router->group(["prefix" => '/users'], function (\Vatts\Router\Router $router) {
                $router->get('/', [\App\controllers\Api\admin\UsersController::class, 'get']);
                $router->post('/create', [\App\controllers\Api\admin\UsersController::class, 'create']);
                $router->post('/delete', [\App\controllers\Api\admin\UsersController::class, 'delete']);
            });

            // Rotas de Nodes
            $router->group(['prefix' => '/nodes'], function (\Vatts\Router\Router $router) {
                $router->get('/online', [\App\controllers\Api\admin\NodesController::class, 'getOnlineNodesByLocation']);
                $router->post('/[nodeId]/allocations', [\App\controllers\Api\admin\NodesController::class, 'getFreeAllocationsByNode']);
                $router->post('/status', [\App\controllers\Api\admin\NodesController::class, 'getNodeStatus']);
            });

        });

        $router->group(['prefix' => '/v1/users/recovery'], function (\Vatts\Router\Router $router) {
            $router->post('/email', [\App\controllers\Api\Users\UsersApiController::class, 'changeEmail'])->middleware('api');
            $router->post('/send', [\App\controllers\Api\Users\UsersApiController::class, 'sendRecoveryEmail']);
            $router->post('/change', [\App\controllers\Api\Users\UsersApiController::class, 'changePassword']);
            $router->get('/validate', [\App\controllers\Api\Users\UsersApiController::class, 'verifyCode']);
        });

        $router->group(["prefix" => '/v1/users', 'middleware' => 'api'], function (\Vatts\Router\Router $router) {
            $router->get('/servers', [\App\controllers\Api\Users\UsersApiController::class, 'getServers']);

            $router->group(["middleware" => 'server', 'prefix' => '/server'], function (\Vatts\Router\Router $router) {
                $router->get('/[server_id]/status', [\App\controllers\Api\Users\UsersApiController::class, 'getStatus']);
                $router->get('/[server_id]', [\App\controllers\Api\Users\UsersApiController::class, 'getServer']);
                $router->post('/[server_id]/action', [\App\controllers\Api\Users\UsersApiController::class, 'sendAction']);
                $router->get('/[server_id]/action/[action]', [\App\controllers\Api\Users\UsersApiController::class, 'sendAction']);

                // modificações
                $router->post("/[server_id]/config", [UsersApiController::class, 'saveNameAndDesc']);

                // startup
                $router->get("/[server_id]/startup", [ServerStartupApiController::class, 'getCoreInfo']);
                $router->post("/[server_id]/startup/docker", [ServerStartupApiController::class, 'saveDockerImage']);
                $router->post("/[server_id]/startup/variable", [ServerStartupApiController::class, 'saveVariable']);

                $router->get("/[server_id]/allocations", [UsersApiController::class, 'getAdditionalAllocations']);
                $router->post("/[server_id]/allocations/add", [UsersApiController::class, 'addAdditionalAllocation']);
                $router->post("/[server_id]/allocations/remove", [UsersApiController::class, 'removeAdditionalAllocation']);

                $router->get("/[server_id]/databases", [\App\controllers\Api\Servers\ServerDatabasesApiController::class, 'getDatabases']);
                $router->post("/[server_id]/databases/create", [\App\controllers\Api\Servers\ServerDatabasesApiController::class, 'createDatabase']);   
                $router->post("/[server_id]/databases/remove", [\App\controllers\Api\Servers\ServerDatabasesApiController::class, 'deleteDatabase']);

                $router->get('/[server_id]/schedulers', [\App\controllers\Api\Users\Servers\ServerSchedulersApiController::class, 'list']);
                $router->post('/[server_id]/schedulers/create', [\App\controllers\Api\Users\Servers\ServerSchedulersApiController::class, 'create']);
                $router->post('/[server_id]/schedulers/delete', [\App\controllers\Api\Users\Servers\ServerSchedulersApiController::class, 'delete']);
                $router->post('/[server_id]/schedulers/toggle', [\App\controllers\Api\Users\Servers\ServerSchedulersApiController::class, 'toggle']);
                $router->post('/[server_id]/schedulers/edit', [\App\controllers\Api\Users\Servers\ServerSchedulersApiController::class, 'edit']);
            });

        });
    }
}