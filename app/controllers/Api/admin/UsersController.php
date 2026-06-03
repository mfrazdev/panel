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
        // [SEGURANÇA] Adicionada a restrição superior (max 72) para mitigar Bcrypt DOS (travamento de CPU)
        $passwordLength = strlen($password);
        if ($passwordLength < 8) {
            return $response->json([
                'success' => false,
                'error'   => 'A senha deve ter no mínimo 8 caracteres.'
            ])->status(400);
        } elseif ($passwordLength > 72) {
            return $response->json([
                'success' => false,
                'error'   => 'A senha não pode ter mais de 72 caracteres.'
            ])->status(400);
        }

        // [SEGURANÇA] Whitelist estrita para regras de atribuição de cargo
        $role = in_array($role, ['admin', 'user']) ? $role : 'user';

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
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $user->role = $role;

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
            // [SEGURANÇA] Information Disclosure: Oculta detalhes do banco de dados na resposta
            return $response->json([
                'success' => false,
                'error'   => 'Erro interno ao criar usuário.'
            ])->status(500);
        }
    }

    /**
     * Busca um usuário pelo ID ou pelo E-mail.
     * Pode ser acessado enviando ?id=... ou ?email=... na URL.
     */
    public function get(Request $request, Response $response): Response
    {
        // [SEGURANÇA] Garante que os inputs de consulta não sejam Arrays (evita quebra de SQL/Erros do PDO)
        $idRaw = $request->getQuery()['id'] ?? null;
        $id = is_scalar($idRaw) ? (string)$idRaw : null;

        $emailRaw = $request->getQuery()['email'] ?? null;
        $email = is_string($emailRaw) ? trim($emailRaw) : null;

        $extRefRaw = $request->getQuery()['external_reference'] ?? null;
        $external_reference = is_string($extRefRaw) ? trim($extRefRaw) : null;

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
            } else if ($external_reference) {
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
            // [SEGURANÇA] Oculta mensagens de erro do banco de dados na resposta
            return $response->json([
                'success' => false,
                'error'   => 'Erro interno ao buscar usuário.'
            ])->status(500);
        }
    }

    /**
     * Deleta um usuário pelo ID.
     */
    public function delete(Request $request, Response $response): Response
    {
        // Pega o ID de forma segura contra Array Injection
        $idRaw = $request->getParam('id') ?? $request->getQuery()['id'] ?? ($request->getBody()['id'] ?? null);
        $id = is_scalar($idRaw) ? (string)$idRaw : null;

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

            // [SEGURANÇA] Bloqueia a autoexclusão através da API (Self-Lockout Protection)
            $currentUser = $request->getParsed('user');
            if ($currentUser && (string)$currentUser->id === (string)$user->id) {
                return $response->json([
                    'success' => false,
                    'error'   => 'Você não pode excluir a sua própria conta administrativa através da API.'
                ])->status(403);
            }

            // [SEGURANÇA] Impede a remoção do último administrador do sistema através da API
            if ($user->role === 'admin') {
                $adminCount = User::witch('role', 'admin')->count();
                if ($adminCount <= 1) {
                    return $response->json([
                        'success' => false,
                        'error'   => 'Não é possível excluir o único administrador restante do sistema.'
                    ])->status(403);
                }
            }

            $user->delete();

            return $response->json([
                'success' => true,
                'message' => 'Usuário excluído com sucesso.'
            ])->status(200);

        } catch (\Exception $e) {
            error_log($e);
            // [SEGURANÇA] Oculta mensagens de erro internas
            return $response->json([
                'success' => false,
                'error'   => 'Erro interno ao excluir usuário.'
            ])->status(500);
        }
    }

    /**
     * Edita a senha de um usuário pelo ID.
     */
    public function edit(Request $request, Response $response): Response
    {
        // Pega o ID de forma segura contra Array Injection
        $idRaw = $request->getParam('id') ?? $request->getQuery()['id'] ?? ($request->getBody()['id'] ?? null);
        $id = is_scalar($idRaw) ? (string)$idRaw : null;

        $body = $request->getBody();
        $password = (string)($body['password'] ?? '');

        if (!$id) {
            return $response->json([
                'success' => false,
                'error'   => 'Forneça o ID do usuário para editar a senha.'
            ])->status(400);
        }

        if ($password === '') {
            return $response->json([
                'success' => false,
                'error'   => 'A nova senha é obrigatória.'
            ])->status(400);
        }

        // Validação de tamanho da senha (Min 8 e Max 72 para Bcrypt DOS protection)
        $passwordLength = strlen($password);
        if ($passwordLength < 8) {
            return $response->json([
                'success' => false,
                'error'   => 'A nova senha deve ter no mínimo 8 caracteres.'
            ])->status(400);
        } elseif ($passwordLength > 72) {
            return $response->json([
                'success' => false,
                'error'   => 'A nova senha não pode ter mais de 72 caracteres.'
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

            // Atualiza apenas a senha, fazendo o hash
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $user->save();

            return $response->json([
                'success' => true,
                'message' => 'Senha atualizada com sucesso.'
            ])->status(200);

        } catch (\Exception $e) {
            error_log($e);
            // [SEGURANÇA] Oculta mensagens de erro internas
            return $response->json([
                'success' => false,
                'error'   => 'Erro interno ao atualizar a senha.'
            ])->status(500);
        }
    }
}