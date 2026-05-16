<?php

namespace App\controllers\Api\Users;

use App\Services\Logger;
use models\User;
use Vatts\Router\Request;
use Vatts\Router\Response;

class UserController
{
    public static function changeEmail(Request $request, Response $response): Response
    {
        $currentPassword = trim($request->getBody()['currentPassword'] ?? '');
        $newEmail = trim($request->getBody()['newEmail'] ?? '');

        if ($currentPassword === '' || $newEmail === '') {
            return $response->json(['error' => 'Todos os campos são obrigatórios.'])->status(400);
        }
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return $response->json(['error' => 'E-mail inválido.'])->status(400);
        }

        $user = $request->getParsed('user');

        if (!password_verify($currentPassword, $user->password)) {
            return $response->json(['error' => 'Senha atual incorreta.'])->status(400);
        }
        if ($user->email === $newEmail) {
            return $response->json(['error' => 'Este já é seu e-mail atual.'])->status(400);
        }
        if (User::get('email', $newEmail)) {
            return $response->json(['error' => 'E-mail já está em uso por outro usuário.'])->status(400);
        }

        $user->email = $newEmail;
        $user->save();
        return $response->json(['success' => true]);
    }

    public static function getServers(Request $request, Response $response): Response
    {
        try {
            $user = $request->getParsed('user');

            if ($user instanceof User) {
                $type = $request->getQuery()['type'] ?? 'not_set';

                if ($user->isAdmin() && $type === 'others') {
                    $serverList = $user->getOthersServers();
                } else {
                    $serverList = $user->getServers();
                }

                $servers = array_map(function ($server) {
                    unset($server->view_map);
                    $allocation = $server->getFirstAllocation()->toArray();
                    unset($allocation["view_map"]);
                    $server->allocation = $allocation;
                    $server->user = $server->getOwnerNameAndEmail();

                    return $server;
                }, $serverList);

                return $response->json(['servers' => $servers]);
            }
            return $response->json(['error' => 'Invalid user.'])->status(400);

        } catch (\Exception $e) {
            Logger::error($e);
            return $response->json([
                'error' => 'An error occurred while fetching servers.',
                'details' => $e->getMessage()
            ])->status(500);
        }
    }
}