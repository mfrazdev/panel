<?php

namespace App\middlewares\api;

use models\Tokens;
use Vatts\Router\Request;
use Vatts\Router\Response;
use Vatts\Utils\Middleware;

class TokenMiddleware extends Middleware
{

    public static string $name = 'token';

    public function handle(Request $request, Response $response): Request|Response
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];

            $tokenmodel = Tokens::get('token', $token);
            if (!$tokenmodel) {
                // [SEGURANÇA] O método status(401) chamado ANTES do json() garante que
                // o código HTTP de bloqueio vá pro cabeçalho de forma consistente.
                return $response->status(401)->json([
                    'error' => true,
                    'message' => 'Unauthorized. Invalid token.'
                ]);
            }

            return $request;
        }

        // Retorno de segurança padronizado para ausência de token
        return $response->status(401)->json([
            'error' => true,
            'message' => 'Unauthorized. Bearer token is required.'
        ]);
    }
}