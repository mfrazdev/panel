<?php

namespace App\middlewares;

use models\User;
use models\Tokens;
use Vatts\Utils\Middleware;
use Vatts\Router\Request;
use Vatts\Router\Response;

class ApiMiddleware extends Middleware
{
    public static string $name = 'api';

    /**
     * [SEGURANÇA] Retorno alterado para Request|Response para permitir bloqueio da rota
     */
    public function handle(Request $request, Response $response): Request|Response
    {
        $auth = require __DIR__ . '/../Auth.php';
        $user = null;

        // 1. Tenta pegar pela Sessão (Cookie/Navegador)
        $session = $auth->getSession();

        if ($session !== null) {
            $user = User::get('id', $session['id']);
        }

        // 2. Se não tem sessão, tenta pelo Bearer Token
        if (!$user) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

            if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];

                // [SEGURANÇA] Corrigida a lógica morta: agora o token é validado no banco
                $tokenModel = Tokens::get('token', $token);

                // Assumindo que seu model Tokens possui uma coluna que relacione ao usuário, como user_id
                if ($tokenModel && isset($tokenModel->user_id)) {
                    $user = User::get('id', $tokenModel->user_id);
                }
            }
        }

        // 3. Se após as duas tentativas continuar nulo, barra a requisição
        if (!$user) {
            // [SEGURANÇA CRÍTICA] Remoção do 'exit;'. Retornar a Response bloqueia a rota no framework
            // de forma segura, permitindo fechamento de DB e envio correto de cabeçalhos.
            return $response->status(401)->json([
                'error' => true,
                'message' => 'Unauthorized.'
            ]);
        }

        // Define o usuário no request para usar no Controller
        $request->setParsed('user', $user);

        return $request;
    }
}