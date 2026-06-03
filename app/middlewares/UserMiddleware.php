<?php

namespace App\Middlewares;

use models\User;
use Vatts\Utils\Middleware;
use Vatts\Router\Request;
use Vatts\Router\Response;

class UserMiddleware extends Middleware
{
    public static string $name = 'user';

    /**
     * [SEGURANÇA] Retorno alterado para Request|Response
     */
    public function handle(Request $request, Response $response): Request|Response
    {
        $auth = require __DIR__ . '/../Auth.php';
        $session = $auth->getSession();

        if ($session !== null) {
            $user = User::get('id', $session['id']);

            // [SEGURANÇA] Impede Fatal Error (Null Pointer) caso a sessão exista mas o usuário
            // tenha sido apagado diretamente no banco de dados.
            if (!$user) {
                $auth->signOut();
                return $response->redirect('/auth');
            }

            $request->setParsed('user', $user);
            return $request;
        }

        $request->setParsed('user', null);

        // [SEGURANÇA CRÍTICA] Retornar o $response trava a execução da rota e aplica o redirect real.
        // O código anterior retornava $request vazando o conteúdo protegido da tela.
        return $response->redirect('/auth');
    }
}