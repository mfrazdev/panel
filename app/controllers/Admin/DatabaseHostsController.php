<?php

namespace App\controllers\Admin;

use App\Services\DatabaseHostsApi;
use App\Services\Logger;
use models\DatabaseHosts;
use models\Server;
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

        $servers = Server::all();

        // fazer um for
        foreach ($servers as $server) {
            try {
                if($server->databases === null) {
                    continue; // Se não tiver bancos, ignora
                }
                $array = json_decode($server->databases, true);
                if (is_array($array)) {
                    foreach ($array as $dbHost) {
                        if (isset($dbHost['hostId']) && $dbHost['hostId'] == $host->id) {
                            return false; // Encontrou um servidor usando esse host, não pode deletar
                        }
                    }
                }
            } catch (\Exception $e) {
                Logger::error("Erro ao decodificar JSON do servidor ID {$server->id}: " . $e->getMessage());
                continue; // Se der erro no JSON, ignora esse servidor
            }
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
        // [SEGURANÇA] Type Safety para evitar Fatal Errors de Array Injection
        $name     = is_string($body['name'] ?? null) ? trim($body['name']) : null;
        $ip       = is_string($body['ip'] ?? null) ? trim($body['ip']) : null;
        $port     = is_scalar($body['port'] ?? null) ? (int)$body['port'] : null;
        $username = is_string($body['username'] ?? null) ? trim($body['username']) : null;

        if (!$name) return "O nome do host é obrigatório.";
        if (!$ip) return "O endereço IP ou hostname é obrigatório.";
        if (!$port || $port < 1 || $port > 65535) return "A porta deve ser um número válido entre 1 e 65535.";
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

        // [SEGURANÇA] Htmlspecialchars protege contra XSS refletido se um DB Host tiver nome malicioso
        $safeName = htmlspecialchars((string)$host->name, ENT_QUOTES, 'UTF-8');

        return $response->view('resources.edit_create', $this->getViewData($request, "Host DB - {$safeName}", $viewData));
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

        // [SEGURANÇA] Cast estrito para prevenir injeções que crashem o banco
        // Além da limpeza para remover o Null Byte que envenena strings de conexão (DSN)
        $host->name     = strip_tags(str_replace("\0", '', (string)($body['name'] ?? '')));
        $host->ip       = strip_tags(str_replace("\0", '', (string)($body['ip'] ?? '')));
        $host->port     = (int) $body['port'];
        $host->username = strip_tags(str_replace("\0", '', (string)($body['username'] ?? '')));

        // A senha no database hosts é salva descriptografada pro PDO ler, portanto validamos o tipo
        $password = isset($body['password']) && is_string($body['password']) ? $body['password'] : '';
        if ($password !== '') {
            $host->password = str_replace("\0", '', $password);
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

        // [SEGURANÇA] Validação de String para evitar falhas do tipo (0 == vazio) ou Array Injection
        $password = isset($body['password']) && is_string($body['password']) ? $body['password'] : '';

        if (!$error && $password === '') {
            $error = "A senha de acesso ao banco de dados é obrigatória.";
        }

        if ($error) {
            return $response->setFlash(['error' => $error])
                ->redirect("/admin/database-hosts/create");
        }

        $host = new DatabaseHosts();

        // [SEGURANÇA] Cast estrito e limpeza de Null Bytes
        $host->name     = strip_tags(str_replace("\0", '', (string)($body['name'] ?? '')));
        $host->ip       = strip_tags(str_replace("\0", '', (string)($body['ip'] ?? '')));
        $host->port     = (int) $body['port'];
        $host->username = strip_tags(str_replace("\0", '', (string)($body['username'] ?? '')));
        $host->password = str_replace("\0", '', $password); // Senha legível/plain-text para a API conseguir conectar

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