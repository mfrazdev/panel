<?php

namespace App\controllers\Admin;

use models\Server;
use models\User;
use Vatts\Router\Request;
use Vatts\Router\Response;

class UsersController
{
    /**
     * Busca o usuário pelo ID
     */
    private function getUser(string $id): ?User
    {
        return User::get("id", $id);
    }


    /**
     * Verifica se o usuário pode ser deletado
     */
    private function canDelete(?User $user, $currentUser): bool
    {
        if (!$user || !$currentUser) {
            return false;
        }

        // Um usuário não pode se deletar
        if ($currentUser->id === $user->id) {
            return false;
        }

        // Verificar se o usuário a ser deletado é o único admin restante
        if ($user->role === 'admin') {
            $adminCount = User::witch('role', 'admin')->count();
            if ($adminCount <= 1) {
                return false;
            }
        }

        $contagem2 = Server::witch('ownerId', $user->id)->count();
        if ($contagem2 > 0) {
            return false;
        }
        return true;
    }

    /**
     * Centraliza os dados repetitivos passados para as views
     */
    private function getViewData(Request $request, string $title, array $extraParams = []): array
    {
        $baseData = [
            'title'         => $title,
            'page_category' => 'admin',
            'page_name'     => 'users_list',
            'user'          => $request->getParsed('user'),
            'backTo'        => '/users',
        ];

        return array_merge($baseData, $extraParams);
    }

    /**
     * Centraliza as validações para Create e Edit
     */
    private function validateUserData(array $body, ?User $editingUser = null, ?User $currentUser = null): ?string
    {
        // [SEGURANÇA] Blindagem contra Array Injection
        $email      = is_string($body['email'] ?? null) ? trim($body['email']) : null;
        $username   = is_string($body['name'] ?? null) ? trim($body['name']) : null;
        $first_name = is_string($body['first_name'] ?? null) ? trim($body['first_name']) : null;
        $last_name  = is_string($body['last_name'] ?? null) ? trim($body['last_name']) : null;
        $role       = is_string($body['role'] ?? null) ? trim($body['role']) : null;

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) return "O email precisa ser um email válido.";
        if (!$username) return "O nome de usuário é obrigatório.";
        if (!$first_name || !$last_name) return "Nome e sobrenome são obrigatórios.";
        if (!$role) return "O cargo (role) deve ser selecionado.";

        // [SEGURANÇA] Whitelist de Cargos (Privilege Escalation protection)
        // Adicione outras roles aqui se existirem na sua aplicação
        $allowedRoles = ['admin', 'user'];
        if (!in_array($role, $allowedRoles)) {
            return "Cargo (role) inválido selecionado.";
        }

        // [SEGURANÇA] Impede o Auto-Bloqueio (Self-Lockout). Um Admin não pode remover sua própria role de admin.
        if ($currentUser && $editingUser && $currentUser->id === $editingUser->id && $role !== $editingUser->role) {
            return "Você não pode alterar seu próprio cargo para evitar a perda do acesso administrativo.";
        }

        // Validar espaços e maiúsculas
        if (preg_match('/\s/', $username)) return "O nome de usuário não pode conter espaços.";
        if (preg_match('/[A-Z]/', $username)) return "O nome de usuário não pode conter letras maiúsculas.";

        // Verifica se o email já existe
        $existingEmailUser = User::get('email', $email);
        if ($existingEmailUser && (!$editingUser || $existingEmailUser->id !== $editingUser->id)) {
            return "O email fornecido já está em uso por outro usuário.";
        }

        // Verifica se o nome de usuário já existe
        $existingUsernameUser = User::get('name', $username);
        if ($existingUsernameUser && (!$editingUser || $existingUsernameUser->id !== $editingUser->id)) {
            return "O nome de usuário fornecido já está em uso por outro usuário.";
        }

        return null; // Sem erros
    }

    // ==============================================================================
    // ACTIONS DA CONTROLLER
    // ==============================================================================

    public function viewAll(Request $request, Response $response): Response
    {
        // [SEGURANÇA] Força cast seguro blindando o sistema contra Array Injection via Query String
        $perPageRaw = $_GET['per_page'] ?? 10;
        $perPage = max(1, (int) (is_scalar($perPageRaw) ? $perPageRaw : 10));

        $pageRaw = $_GET['page'] ?? 1;
        $page = max(1, (int) (is_scalar($pageRaw) ? $pageRaw : 1));

        $allUsers = User::all();

        $totalItems = count($allUsers);

        $users = array_slice(
            $allUsers,
            ($page - 1) * $perPage,
            $perPage
        );

        $lastPage = (int) ceil($totalItems / $perPage);

        $pagination = [
            'current_page' => $page,
            'last_page'    => max(1, $lastPage),
            'total'        => $totalItems,
            'from'         => $totalItems > 0 ? (($page - 1) * $perPage) + 1 : 0,
            'to'           => min($page * $perPage, $totalItems),
        ];

        $map = [
            ['label' => 'Identificador', 'key' => 'id', 'type' => 'text'],
            ['label' => 'Usuário', 'key' => 'name', 'type' => 'user'],
            ['label' => 'Data de Criação', 'key' => 'created_at', 'type' => 'date'],
        ];

        $viewData = [
            'resources'  => $users,
            'map'        => $map,
            'see'        => 'users/[id]/edit',
            'create'     => 'users/create',
            'delete'     => 'users/[id]/delete?return=all',
            'pagination' => $pagination
        ];

        return $response->view(
            'resources.view_resources',
            $this->getViewData($request, 'Usuários', $viewData)
        );
    }

    public function viewEdit(Request $request, Response $response): Response
    {
        $user = $this->getUser($request->getParam("user"));

        if (!$user) {
            return $response->view("resources.resource_not_found", ['title' => 'usuário']);
        }

        $viewData = [
            'resource'  => $user,
            'map'       => $user->view_map,
            'canDelete' => $this->canDelete($user, $request->getParsed('user')),
            'deleteUrl' => 'users/[id]/delete?return=edit',
            'tabs' => false
        ];

        // [SEGURANÇA] Proteção contra XSS no Título
        // Se um atacante usar tags <script> no nome, a view poderia executá-las ao renderizar a string concatenada.
        $safeFirstName = htmlspecialchars((string)$user->first_name, ENT_QUOTES, 'UTF-8');
        $safeLastName  = htmlspecialchars((string)$user->last_name, ENT_QUOTES, 'UTF-8');

        return $response->view('resources.edit_create', $this->getViewData($request, "Usuário - {$safeFirstName} {$safeLastName}", $viewData));
    }

    public function edit(Request $request, Response $response): Response
    {
        $user = $this->getUser($request->getParam("user"));

        if (!$user) {
            return $response->view("resources.resource_not_found", ['title' => 'usuário']);
        }

        $body = $request->getBody();
        $currentUser = $request->getParsed('user');

        $error = $this->validateUserData($body, $user, $currentUser);

        // Se houve erro, retorna a view com o erro
        if ($error) {
            return $response->setFlash(['error' => $error])
                ->redirect("/admin/users/{$user->id}/edit");
        }

        // [SEGURANÇA] Cast rígido na inserção para evitar que PDO exploda com Fatal Error se receber arrays
        $user->name       = (string)$body['name'];
        $user->email      = (string)$body['email'];
        $user->first_name = (string)$body['first_name'];
        $user->last_name  = (string)$body['last_name'];
        $user->role       = (string)$body['role'];

        // [SEGURANÇA] Tratamento do password para corrigir falha do '0' e evitar DOS do hash com limites
        $password = isset($body['password']) && is_string($body['password']) ? $body['password'] : '';

        if ($password !== '') {
            if (strlen($password) > 72) {
                return $response->setFlash(['error' => 'A senha não pode ter mais de 72 caracteres.'])
                    ->redirect("/admin/users/{$user->id}/edit");
            }
            $user->password = password_hash($password, PASSWORD_DEFAULT);
        }

        $user->save();

        // Redireciona com flash message de sucesso
        return $response->setFlash(['success' => 'O usuário foi editado com sucesso!'])
            ->redirect("/admin/users/{$user->id}/edit");
    }

    public function viewCreate(Request $request, Response $response): Response
    {
        $user = new User();
        $map = $user->view_map;
        $map["Segurança"] = [['label' => 'Senha', 'key' => 'password', 'type' => 'password', 'desc' => '']];

        return $response->view('resources.edit_create', $this->getViewData($request, 'Usuário', [
            'map'       => $map,
            'see'       => 'users/[id]/edit',
            'canDelete' => false,
            'deleteUrl' => 'users/[id]/delete?return=edit'
        ]));
    }

    public function create(Request $request, Response $response): Response
    {
        $body = $request->getBody();
        $currentUser = $request->getParsed('user');

        $error = $this->validateUserData($body, null, $currentUser);

        $password = isset($body['password']) && is_string($body['password']) ? $body['password'] : '';

        // [SEGURANÇA] Correção do Bug do Password '0' (que o empty() entendia como falso/nulo) e verificação de limite
        if (!$error && $password === '') {
            $error = "A senha é obrigatória.";
        } elseif (!$error && strlen($password) > 72) {
            $error = "A senha não pode ter mais de 72 caracteres.";
        }

        $user = new User();

        if ($error) {
            return $response->setFlash(['error' => $error])
                ->redirect("/admin/users/create");
        }

        // [SEGURANÇA] Cast rígido na inserção PDO
        $user->name       = (string)$body['name'];
        $user->email      = (string)$body['email'];
        $user->first_name = (string)$body['first_name'];
        $user->last_name  = (string)$body['last_name'];
        $user->role       = (string)$body['role'];
        $user->password   = password_hash($password, PASSWORD_DEFAULT);

        $user->save();

        // Redireciona para a página de edição do novo usuário com sucesso
        return $response->setFlash(['success' => 'O usuário foi criado com sucesso!'])
            ->redirect("/admin/users/{$user->id}/edit");
    }

    public function delete(Request $request, Response $response): Response
    {
        $userId = $request->getParam("user");
        $user = $this->getUser($userId);
        $currentUser = $request->getParsed('user');

        $baseRoute = '/admin/users';

        if (!$user) {
            return $response->setFlash(['error' => 'Usuário não encontrado'])
                ->redirect($baseRoute);
        }

        if (!$this->canDelete($user, $currentUser)) {
            $returnTo = $request->getQuery()['return'] ?? 'edit';
            $errorMsg = 'Não é possível excluir este usuário.';

            if ($returnTo === 'all') {
                $redirect = $baseRoute;
            } else {
                $redirect = "{$baseRoute}/{$userId}/edit";
            }

            return $response->setFlash(['error' => $errorMsg])
                ->redirect($redirect);
        }

        $user->delete();

        $successMsg = 'O usuário foi excluído com sucesso!';

        return $response->setFlash(['success' => $successMsg])
            ->redirect($baseRoute);
    }
}