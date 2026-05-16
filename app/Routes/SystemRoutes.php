<?php

namespace App\Routes;

use Vatts\Router\Router;
use Vatts\Router\Request;
use Vatts\Router\Response;
use models\Settings;
use App\controllers\Api\NodesHelper;
use App\controllers\Api\Users\UsersApiController;

class SystemRoutes
{
    public static function setup(Router $router): void
    {
        $router->group(["prefix" => '/v1/auth'], function (Router $router) {
            $router->group(["prefix" => '/captcha'], function (Router $router) {
                $router->get('/config', [\App\controllers\CaptchaController::class, 'getConfig']);
                $router->post('/validate', [\App\controllers\CaptchaController::class, 'validateToken']); // Opcional
            });

            $router->get('/billing/status', function (Request $request, Response $response) {
                $systemSetting = Settings::get('key', 'billing_system');
                $activeSystem = ($systemSetting !== null && $systemSetting->value !== '') ? $systemSetting->value : 'none';

                return $response->json([
                    'success'   => true,
                    'is_active' => ($activeSystem !== 'none'),
                    'system'    => $activeSystem
                ]);
            });


        });

        // ==========================================
        // Rotas do Helper de Nodes
        // ==========================================
        $router->group(["prefix" => "/nodes/helper"], function (Router $router) {
            $router->post('/admin-permission', [NodesHelper::class, 'isAdmin']);
            $router->post('/permission', [NodesHelper::class, 'permission']);
            $router->post('/verify-sftp', [NodesHelper::class, 'verifysftp']);
        });

        // ==========================================
        // Rotas de Recuperação de Conta (Deslogado / Públicas)
        // ==========================================
        $router->group(['prefix' => '/v1/users/recovery'], function (Router $router) {
            $router->post('/send', [UsersApiController::class, 'sendRecoveryEmail']);
            $router->post('/change', [UsersApiController::class, 'changePassword']);
            $router->get('/validate', [UsersApiController::class, 'verifyCode']);
        });
    }
}