<?php

namespace App\controllers\Admin;

use App\Services\DatabaseHostsApi;
use models\DatabaseHosts;
use Vatts\Router\Request;
use Vatts\Router\Response;

class DatabaseHostsController
{
    /**
     * Busca o host pelo ID
     */
    private function getHost(string $id): ?DatabaseHosts
    {
        return DatabaseHosts::get("id", $id);
    }

    /**
     * Verifica se o host pode ser deletado
     */
    private function canDelete(?DatabaseHosts $host): bool
    {
        if (!$host) {
            return false;
        }

        // TODO: verificar se existem bancos de dados ou servidores vinculados a este host antes de deletar
        // Se houver servidores usando este host, retornar false.

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
            'page_name'     => 'database_hosts_list',
            'user'          => $request->getParsed('user'), // Usuário logado
            'backTo'        => '/admin/database-hosts',
        ];

        return array_merge($baseData, $extraParams);
    }

    /**
     * Centraliza as validações para Create e Edit
     */
    private function validateHostData(array $body, ?DatabaseHosts $currentHost = null): ?string
    {
        $name     = $body['name'] ?? null;
        $ip       = $body['ip'] ?? null;
        $port     = $body['port'] ?? null;
        $username = $body['username'] ?? null;

        if (!$name) return "O nome do host é obrigatório.";
        if (!$ip) return "O endereço IP ou hostname é obrigatório.";
        if (!$port || !is_numeric($port) || $port < 1 || $port > 65535) return "A porta deve ser um número válido entre 1 e 65535.";
        if (!$username) return "O usuário administrador do banco é obrigatório.";

        // Verifica se o IP + Porta já existe
        $existingHost = DatabaseHosts::where('ip', $ip); // Isso retorna um array na maioria dos ORMs, adapte se o seu 'where' for diferente
        if (is_array($existingHost)) {
            foreach ($existingHost as $h) {
                if ($h->port == $port && (!$currentHost || $h->id !== $currentHost->id)) {
                    return "Já existe um Host cadastrado com este IP e Porta.";
                }
            }
        }

        return null; // Sem erros
    }

    // ==============================================================================
    // ACTIONS DA CONTROLLER
    // ==============================================================================

    public function viewAll(Request $request, Response $response): Response
    {
        $hosts = DatabaseHosts::all();

        $map = [
            ['label' => 'Identificador', 'key' => 'id', 'type' => 'text'],
            ['label' => 'Nome', 'key' => 'name', 'type' => 'text'],
            ['label' => 'IP do Host', 'key' => 'ip', 'type' => 'text'],
            ['label' => 'Porta', 'key' => 'port', 'type' => 'text']
        ];

        $viewData = [
            'resources' => $hosts,
            'map'       => $map,
            'see'       => 'database-hosts/[id]/edit',
            'create'    => 'database-hosts/create',
            'delete'    => 'database-hosts/[id]/delete?return=all'
        ];

        return $response->view('resources.view_resources', $this->getViewData($request, 'Hosts de Banco de Dados', $viewData));
    }

    public function viewEdit(Request $request, Response $response): Response
    {
        $host = $this->getHost($request->getParam("id")); // Ajustado para pegar "id" padrão

        if (!$host) {
            return $response->view("resources.resource_not_found", ['title' => 'Host de banco de dados']);
        }

        $viewData = [
            'resource'  => $host,
            'map'       => $host->view_map,
            'canDelete' => $this->canDelete($host),
            'deleteUrl' => 'database-hosts/[id]/delete?return=edit'
        ];

        return $response->view('resources.edit_create', $this->getViewData($request, "Host DB - {$host->name}", $viewData));
    }

    public function edit(Request $request, Response $response): Response
    {
        $host = $this->getHost($request->getParam("id"));

        if (!$host) {
            return $response->view("resources.resource_not_found", ['title' => 'Host de banco de dados']);
        }

        $body = $request->getBody();
        $error = $this->validateHostData($body, $host);

        if ($error) {
            return $response->setFlash(['error' => $error])
                ->redirect("/admin/database-hosts/{$host->id}/edit");
        }

        $host->name     = $body['name'];
        $host->ip       = $body['ip'];
        $host->port     = (int) $body['port'];
        $host->username = $body['username'];

        if (!empty($body['password'])) {
            $host->password = $body['password']; // Diferente de usuários, aqui guardamos plaintext/criptografado pelo seu sistema para o PDO ler dps
        }

        $host->save();

        return $response->setFlash(['success' => 'O host de banco de dados foi atualizado com sucesso!'])
            ->redirect("/admin/database-hosts/{$host->id}/edit");
    }

    public function viewCreate(Request $request, Response $response): Response
    {
        $host = new DatabaseHosts();
        $map = $host->view_map;
        
        // Se precisar injetar senhas no mapeamento para criar
        $map["Geral"][] = ['label' => 'Nome de Identificação', 'key' => 'name', 'type' => 'text', 'desc' => 'Ex: Node BR-1'];

        return $response->view('resources.edit_create', $this->getViewData($request, 'Novo Host de Banco de Dados', [
            'map'       => $map,
            'canDelete' => false,
            'deleteUrl' => 'database-hosts/[id]/delete?return=edit'
        ]));
    }

    public function create(Request $request, Response $response): Response
    {
        $body = $request->getBody();
        $error = $this->validateHostData($body);

        if (!$error && empty($body['password'])) {
            $error = "A senha de acesso ao banco de dados é obrigatória.";
        }

        if ($error) {
            return $response->setFlash(['error' => $error])
                ->redirect("/admin/database-hosts/create");
        }

        $host = new DatabaseHosts();
        $host->name     = $body['name'];
        $host->ip       = $body['ip'];
        $host->port     = (int) $body['port'];
        $host->username = $body['username'];
        $host->password = $body['password']; // Senha legível/plain-text para a API conseguir conectar
        $host->save();

        return $response->setFlash(['success' => 'O host foi adicionado com sucesso!'])
            ->redirect("/admin/database-hosts/{$host->id}/edit");
    }

    public function delete(Request $request, Response $response): Response
    {
        $hostId = $request->getParam("id");
        $host = $this->getHost($hostId);

        $baseRoute = '/admin/database-hosts';

        if (!$host) {
            return $response->setFlash(['error' => 'Host não encontrado.'])
                ->redirect($baseRoute);
        }

        if (!$this->canDelete($host)) {
            $returnTo = $request->getQuery()['return'] ?? 'edit';
            $errorMsg = 'Não é possível excluir este host pois ele já tem bancos em uso.';

            $redirect = $returnTo === 'all' ? $baseRoute : "{$baseRoute}/{$hostId}/edit";

            return $response->setFlash(['error' => $errorMsg])
                ->redirect($redirect);
        }

        $host->delete();

        return $response->setFlash(['success' => 'Host excluído com sucesso!'])
            ->redirect($baseRoute);
    }

    /**
     * Action extra para testar a conexão com a API
     */
    public function testConnectionAction(Request $request, Response $response): Response
    {
        $hostId = $request->getParam("id");
        $host = $this->getHost($hostId);

        if (!$host) {
            return $response->setFlash(['error' => 'Host não encontrado.'])->redirect("/admin/database-hosts");
        }

        $api = new DatabaseHostsApi($host);
        if ($api->testConnection()) {
            return $response->setFlash(['success' => 'Conexão estabelecida com o servidor MySQL com sucesso!'])
                ->redirect("/admin/database-hosts/{$host->id}/edit");
        }

        return $response->setFlash(['error' => 'Falha ao conectar no servidor. Verifique IP, Porta, Usuário e Senha.'])
            ->redirect("/admin/database-hosts/{$host->id}/edit");
    }
}