<?php

namespace App\middlewares;

use models\User;
use Vatts\Utils\BladeConfig;
use Vatts\Utils\Middleware;
use Vatts\Router\Request;
use Vatts\Router\Response;

class AdminMiddleware extends Middleware
{
    public static string $name = 'admin';

    /**
     * O tipo de retorno foi ajustado para Request|Response para permitir
     * que a execução pare (retornando Response) ou continue (retornando Request)
     */
    public function handle(Request $request, Response $response): Request|Response
    {
        $auth = require __DIR__ . '/../Auth.php';
        $session = $auth->getSession();

        if ($session !== null) {
            $user = User::get('id', $session['id']);

            // [SEGURANÇA] Verifica se o usuário logado realmente ainda existe no banco
            if (!$user) {
                $auth->signOut();
                // [SEGURANÇA CRÍTICA] Retornar o $response trava a rota imediatamente
                return $response->redirect('/auth');
            }

            $request->setParsed('user', $user);

            if ($user->role !== 'admin') {
                // [SEGURANÇA CRÍTICA] Retornar $response! Se retornasse $request,
                // a rota restrita de admin seria executada mesmo com o redirect agendado.
                return $response->redirect('/');
            }
        } else {
            $request->setParsed('user', null);

            // [SEGURANÇA CRÍTICA] Bloqueia a execução encadeada retornando a Response
            return $response->redirect('/auth');
        }
        $usersCount = \models\User::count();
        $databasesCount = \models\DatabaseHosts::count();
        $coresCount = \models\Core::count();
        $serversCount = \models\Server::count();
        $nodesCount = \models\Node::count();

        BladeConfig::get()->share('usersCount', $usersCount);
        BladeConfig::get()->share('databasesCount', $databasesCount);
        BladeConfig::get()->share('coresCount', $coresCount);
        BladeConfig::get()->share('serversCount', $serversCount);
        BladeConfig::get()->share('nodesCount', $nodesCount);
        // Tudo certo (Usuário existe, está logado e é admin).
        // Retorna o Request para o Router liberar o acesso ao Controller.
        return $request;
    }
}