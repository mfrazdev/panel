<?php

namespace App\controllers\Admin;

use models\Tokens;
use Vatts\Router\Request;
use Vatts\Router\Response;

class TokensController
{
    /**
     * Busca o token pelo ID
     */
    private function getToken(string $id): ?Tokens
    {
        return Tokens::get("id", $id);
    }



    /**
     * Centraliza os dados repetitivos passados para as views
     */
    private function getViewData(Request $request, string $title, array $extraParams = []): array
    {
        $baseData = [
            'title'         => $title,
            'page_category' => 'admin',
            'page_name'     => 'api_tokens_list',
            'user'          => $request->getParsed('user'), // Usuário logado
            'backTo'        => '/tokens',
        ];

        return array_merge($baseData, $extraParams);
    }

    /**
     * Centraliza as validações para Create e Edit
     */
    private function validateTokenData(array $body): ?string
    {
        $name = $body['name'] ?? null;

        if (!$name) return "O nome do token é obrigatório.";

        return null; // Sem erros
    }

    // ==============================================================================
    // ACTIONS DA CONTROLLER
    // ==============================================================================

    public function viewAll(Request $request, Response $response): Response
    {
        $tokens = Tokens::all();

        $map = [
            ['label' => 'Identificador', 'key' => 'id', 'type' => 'text'],
            ['label' => 'Nome', 'key' => 'name', 'type' => 'text'],
            ['label' => 'Token', 'key' => 'token', 'type' => 'text'],
            ['label' => 'Descrição', 'key' => 'desc', 'type' => 'text']
        ];

        $viewData = [
            'resources' => $tokens,
            'map'       => $map,
            'see'       => 'tokens/[id]/edit',
            'create'    => 'tokens/create',
            'delete'    => 'tokens/[id]/delete?return=all'
        ];

        return $response->view('resources.view_resources', $this->getViewData($request, 'Tokens de API', $viewData));
    }

    public function viewEdit(Request $request, Response $response): Response
    {
        $token = $this->getToken($request->getParam("id"));

        if (!$token) {
            return $response->view("resources.resource_not_found", ['title' => 'Token de API']);
        }

        $viewData = [
            'resource'  => $token,
            'map'       => $token->view_map,
            'canDelete' => true,
            'deleteUrl' => 'tokens/[id]/delete?return=edit'
        ];

        return $response->view('resources.edit_create', $this->getViewData($request, "Token API - {$token->name}", $viewData));
    }

    public function edit(Request $request, Response $response): Response
    {
        $token = $this->getToken($request->getParam("id"));

        if (!$token) {
            return $response->view("resources.resource_not_found", ['title' => 'Token de API']);
        }

        $body = $request->getBody();
        $error = $this->validateTokenData($body);

        if ($error) {
            return $response->setFlash(['error' => $error])
                ->redirect("/admin/tokens/{$token->id}/edit");
        }

        $token->name = $body['name'];
        $token->desc = $body['desc'] ?? '';

        // A string do token em si não costuma ser editável após criada.
        $token->save();

        return $response->setFlash(['success' => 'O token de API foi atualizado com sucesso!'])
            ->redirect("/admin/tokens/{$token->id}/edit");
    }

    public function viewCreate(Request $request, Response $response): Response
    {
        $token = new Tokens();
        $map = $token->view_map;

        return $response->view('resources.edit_create', $this->getViewData($request, 'Novo Token de API', [
            'map'       => $map,
            'canDelete' => false,
            'deleteUrl' => 'tokens/[id]/delete?return=edit'
        ]));
    }

    public function create(Request $request, Response $response): Response
    {
        $body = $request->getBody();
        $error = $this->validateTokenData($body);

        if ($error) {
            return $response->setFlash(['error' => $error])
                ->redirect("/admin/tokens/create");
        }

        $token = new Tokens();
        $token->name  = $body['name'];
        $token->desc  = $body['desc'] ?? '';

        // Gera uma string aleatória segura de 64 caracteres (32 bytes em hex)
        $token->token = bin2hex(random_bytes(32));

        $token->save();

        return $response->setFlash(['success' => 'O Token de API foi criado com sucesso! Guarde-o em um local seguro.'])
            ->redirect("/admin/tokens");
    }

    public function delete(Request $request, Response $response): Response
    {
        $tokenId = $request->getParam("id");
        $token = $this->getToken($tokenId);

        $baseRoute = '/admin/tokens';

        if (!$token) {
            return $response->setFlash(['error' => 'Token não encontrado.'])
                ->redirect($baseRoute);
        }

        $token->delete();

        return $response->setFlash(['success' => 'Token excluído (revogado) com sucesso!'])
            ->redirect($baseRoute);
    }
}