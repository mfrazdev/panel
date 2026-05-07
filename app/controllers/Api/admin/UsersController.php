<?php

namespace App\controllers\Api\admin;

use models\User;
use Vatts\Router\Request;
use Vatts\Router\Response;

class UsersController
{
    /**
     * Cria um novo usuário na API.
     */
    public function create(Request $request, Response $response): Response
    {
        $body = $request->getBody();

        $name = trim((string)($body['name'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');
        $firstName = trim((string)($body['first_name'] ?? ''));
        $lastName = trim((string)($body['last_name'] ?? ''));
        $role = trim((string)($body['role'] ?? 'user'));
        $externalReference = trim((string)($body['external_reference'] ?? ''));

        // 1. Validação de campos obrigatórios
        if ($name === '' || $email === '' || $password === '') {
            return $response->json([
                'success' => false,
                'error'   => 'Campos obrigatórios ausentes: name, email ou password.'
            ])->status(400); // 400 Bad Request
        }

        // 2. Validação de tamanho da senha
        if (strlen($password) < 8) {
            return $response->json([
                'success' => false,
                'error'   => 'A senha deve ter no mínimo 8 caracteres.'
            ])->status(400);
        }

        try {
            // 3. Verificação de E-mail duplicado
            $existingEmail = User::where('email', $email);
            if (!empty($existingEmail)) {
                return $response->json([
                    'success' => false,
                    'error'   => 'Já existe um usuário cadastrado com este e-mail.'
                ])->status(409); // 409 Conflict
            }

            // 4. Verificação de Nome de Usuário (name) duplicado
            $existingName = User::where('name', $name);
            if (!empty($existingName)) {
                return $response->json([
                    'success' => false,
                    'error'   => 'Já existe um usuário cadastrado com este nome de usuário.'
                ])->status(409);
            }

            // 5. Criação do Usuário
            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->first_name = $firstName;
            $user->last_name = $lastName;
            // É fundamental fazer o hash da senha
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $user->role = in_array($role, ['admin', 'user']) ? $role : 'user';

            if ($externalReference !== '') {
                $user->external_reference = $externalReference;
            }

            $user->save();

            // Removemos dados que não devem ser retornados na API
            unset($user->password);
            unset($user->view_map);

            return $response->json([
                'success' => true,
                'message' => 'Usuário criado com sucesso.',
                'data'    => $user
            ])->status(201); // 201 Created

        } catch (\Exception $e) {
            error_log($e); // Log do erro para análise posterior
            return $response->json([
                'success' => false,
                'error'   => 'Erro ao criar usuário: ' . $e->getMessage()
            ])->status(500);
        }
    }

    /**
     * Busca um usuário pelo ID ou pelo E-mail.
     * Pode ser acessado enviando ?id=... ou ?email=... na URL.
     */
    public function get(Request $request, Response $response): Response
    {
        $id = $request->getQuery()['id'] ?? null;
        $email = $request->getQuery()['email'] ?? null;
        $external_reference = $request->getQuery()['external_reference'] ?? null;

        if (!$id && !$email && !$external_reference) {
            return $response->json([
                'success' => false,
                'error'   => 'Forneça o id, referência externa, ou o email do usuário na requisição para realizar a busca.'
            ])->status(400);
        }

        try {
            $user = null;

            if ($id) {
                // Busca por ID
                $user = User::find($id);
            } else if ($email) {
                // Busca por E-mail
                $users = User::where('email', $email);
                // Como where retorna um array, pegamos o primeiro item se existir
                if (!empty($users)) {
                    $user = $users[0] ?? current($users);
                }
            } else if($external_reference) {
                // Busca por Referência Externa
                $users = User::where('external_reference', $external_reference);
                if (!empty($users)) {
                    $user = $users[0] ?? current($users);
                }
            }

            if (!$user) {
                return $response->json([
                    'success' => false,
                    'error'   => 'Usuário não encontrado.'
                ])->status(404);
            }

            // Removemos os dados sensíveis/inúteis
            unset($user->password);
            unset($user->view_map);

            return $response->json([
                'success' => true,
                'data'    => $user
            ])->status(200);

        } catch (\Exception $e) {
            error_log($e);
            return $response->json([
                'success' => false,
                'error'   => 'Erro ao buscar usuário: ' . $e->getMessage()
            ])->status(500);
        }
    }

    /**
     * Deleta um usuário pelo ID.
     */
    public function delete(Request $request, Response $response): Response
    {
        // Pega o ID da rota (ex: /api/admin/users/[id]), da query string (?id=) ou do corpo da requisição
        $id = $request->getParam('id') ?? $request->getQuery()['id'] ?? ($request->getBody()['id'] ?? null);

        if (!$id) {
            return $response->json([
                'success' => false,
                'error'   => 'Forneça o ID do usuário para realizar a exclusão.'
            ])->status(400);
        }

        try {
            $user = User::find($id);

            if (!$user) {
                return $response->json([
                    'success' => false,
                    'error'   => 'Usuário não encontrado.'
                ])->status(404);
            }

            $user->delete();

            return $response->json([
                'success' => true,
                'message' => 'Usuário excluído com sucesso.'
            ])->status(200);

        } catch (\Exception $e) {
            error_log($e);
            return $response->json([
                'success' => false,
                'error'   => 'Erro ao excluir usuário: ' . $e->getMessage()
            ])->status(500);
        }
    }
}