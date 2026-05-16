<?php

namespace App\Routes;

use App\controllers\Api\NodesHelper;
use App\controllers\Api\System\AuthController;
use App\controllers\Api\Users\UsersApiController;
use models\Settings;
use Vatts\Router\Request;
use Vatts\Router\Response;
use Vatts\Router\Router;

class SystemRoutes
{
    public static function setup(Router $router): void
    {
        $router->group(["prefix" => '/v1/auth'], function (Router $router) {
            $router->group(["prefix" => '/captcha'], function (Router $router) {
                $router->get('/config', [\App\controllers\Api\System\CaptchaController::class, 'getConfig']);
                $router->post('/validate', [\App\controllers\Api\System\CaptchaController::class, 'validateToken']); // Opcional
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
            $router->post('/send', [AuthController::class, 'sendRecoveryEmail']);
            $router->post('/change', [AuthController::class, 'changePassword']);
            $router->get('/validate', [AuthController::class, 'verifyCode']);
        });
    }
}