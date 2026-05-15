<?php

namespace App\controllers\Admin;

require_once __DIR__ . '/../../Utils/EnvVarUtils.php';

use App\Services\ServerService;
use models\Server;
use models\Node;
use models\Core;
use models\Allocation;
use App\Utils\EnvVarUtils;
use models\User;
use Vatts\Router\Request;
use Vatts\Router\Response;
use Vatts\Database\DB;

class ServerController
{
    private ServerService $serverService;

    public function __construct()
    {
        $this->serverService = new ServerService();
    }

    /**
     * Salva temporariamente os inputs na sessão para restaurar em caso de erro
     */
    private function flashOldInput(array $data): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['_old_input'] = $data;
    }

    /**
     * Recupera os inputs salvos e limpa a sessão
     */
    private function getOldInput(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $old = $_SESSION['_old_input'] ?? [];
        unset($_SESSION['_old_input']);
        return is_array($old) ? $old : [];
    }

    private function getNode(string $id): ?Node
    {
        return Node::find($id);
    }

    private function getCore(string $id): ?Core
    {
        return Core::find($id);
    }

    private function getViewData(Request $request, string $title, array $extraParams = []): array
    {
        $baseData = [
            'title'         => $title,
            'page_category' => 'admin',
            'page_name'     => 'servers',
            'user'          => $request->getParsed('user'),
            'backTo'        => '/servers',
        ];

        return array_merge($baseData, $extraParams);
    }

    // Recebe o array $old para preencher os valores padrão caso a página recarregue com erro
    private function getCreateMap(array $old = []): array
    {
        return [
            'Informações Básicas' => [
                ['label' => 'Nome do Servidor', 'key' => 'name', 'type' => 'text', 'desc' => 'Nome de exibição do servidor.', 'required' => true, 'default' => $old['name'] ?? ''],
                ['label' => 'Descrição', 'key' => 'description', 'type' => 'textarea', 'desc' => 'Breve descrição do servidor.', 'default' => $old['description'] ?? '']
            ],
            'Recursos' => [
                ['label' => 'Memória RAM (MB)', 'key' => 'ram', 'type' => 'number', 'desc' => 'Quantidade de RAM em MB. Deixe em 0 para ilimitado.', 'default' => $old['ram'] ?? '', 'required' => true],
                ['label' => 'CPU (%)', 'key' => 'cpu', 'type' => 'number', 'desc' => 'Porcentagem de CPU. Deixe em 0 para ilimitado.', 'default' => $old['cpu'] ?? '100', 'required' => true],
                ['label' => 'Disco (MB)', 'key' => 'disk', 'type' => 'number', 'desc' => 'Espaço em disco em MB. Deixe em 0 para ilimitado.', 'default' => $old['disk'] ?? '0', 'required' => true],
            ],
            "Cotas" => [
                ['label' => 'Máx. Alocações Adicionais do Usuário', 'key' => 'maxAdditionalAllocations', 'type' => 'number', 'desc' => 'Quantidade máxima de alocações adicionais que o usuário pode adicionar sozinho. Deixe vazio para ilimitado.', 'default' => $old['maxAdditionalAllocations'] ?? '0'],
                ['label' => 'Máx. Bancos de Dados', 'key' => 'maxDatabases', 'type' => 'number', 'desc' => 'Quantidade máxima de bancos de dados que este servidor pode ter. Deixe vazio para ilimitado.', 'default' => $old['maxDatabases'] ?? '0'],
            ]
        ];
    }

    public function viewCreate(Request $request, Response $response): Response
    {
        // Resgata os inputs antigos, caso existam
        $old = $this->getOldInput();

        // Tenta recuperar o ownerUser caso ele já tenha sido preenchido
        $ownerUser = null;
        if (!empty($old['ownerId'])) {
            $ownerUser = User::find($old['ownerId']);
        }

        return $response->view('resources.edit_create', $this->getViewData($request, 'Servidor', [
            'map' => $this->getCreateMap($old), // Envia os inputs antigos pros campos genéricos
            'isBelow' => true,
            'old' => $old, // Disponibiliza a variável $old na view para cards customizados
            'ownerUser' => $ownerUser,
            'custom_cards' => [
                'partials.server.cards.allocation',
                'partials.server.cards.core_docker',
                'partials.server.cards.startup',
                'partials.server.cards.env',
            ],
        ]));
    }

    public function create(Request $request, Response $response): Response
    {
        $body = $request->getBody();

        try {
            // Lógica delegada para o Service
            $server = $this->serverService->createServer($body);
            return $response->setFlash(['success' => 'Servidor criado com sucesso e requisitado ao node'])->redirect("/admin/servers/{$server->id}/edit");
        } catch (\Exception $e) {
            $this->flashOldInput($body);
            return $response->setFlash(['error' => $e->getMessage()])->redirect('/admin/servers/create');
        }
    }

    public function viewAll(Request $request, Response $response): Response
    {
        $existingServers = Server::all();

        $ownerIds = [];
        $rawOwnerEmails = []; // Novo array para armazenar o email isolado

        $nodeIds = []; // Novo array para filtros da Node
        $rawNodeNames = []; // Array para armazenar o nome da Node isolado

        $pdo = DB::getPdo();
        foreach ($existingServers as $server) {
            // Evita fazer a mesma query para o mesmo Dono várias vezes
            if (!empty($server->ownerId) && !isset($rawOwnerEmails[$server->ownerId])) {
                $owner = $pdo->prepare("SELECT `email` FROM `users` WHERE `id` = :ownerId LIMIT 1");
                $owner->execute(['ownerId' => $server->ownerId]);
                $ownerEmail = $owner->fetchColumn();

                if ($ownerEmail) {
                    $rawOwnerEmails[$server->ownerId] = $ownerEmail; // Guarda apenas o e-mail limpo
                    $ownerIds[$server->ownerId] = $ownerEmail . ' (ID: ' . $server->ownerId . ')';
                } else {
                    $rawOwnerEmails[$server->ownerId] = 'Dono ID ' . $server->ownerId;
                    $ownerIds[$server->ownerId] = 'Dono ID ' . $server->ownerId;
                }
            }

            // Lógica para otimizar a busca das Nodes da mesma forma que os Donos
            if (!empty($server->nodeUuid) && !isset($rawNodeNames[$server->nodeUuid])) {
                $node = $pdo->prepare("SELECT `name` FROM `nodes` WHERE `id` = :nodeId LIMIT 1");
                error_log("Query para Node ID {$server->nodeUuid}: " . $node->queryString);
                $node->execute(['nodeId' => $server->nodeUuid]);
                $nodeName = $node->fetchColumn();

                if ($nodeName) {
                    $rawNodeNames[$server->nodeUuid] = $nodeName;
                    $nodeIds[$server->nodeUuid] = $nodeName . ' (ID: ' . $server->nodeUuid . ')';
                } else {
                    $rawNodeNames[$server->nodeUuid] = 'Node ID ' . $server->nodeUuid;
                    $nodeIds[$server->nodeUuid] = 'Node ID ' . $server->nodeUuid;
                }
            }
        }

        ksort($ownerIds);
        ksort($nodeIds);

        $filters = [
            'ownerId' => [
                'name' => 'Dono',
                'keys' => $ownerIds
            ],
            'nodeId' => [
                'name' => 'Node',
                'keys' => $nodeIds
            ]
        ];

        $query = Server::witch([]);

        foreach ($filters as $key => $filter) {
            if (!empty($_GET[$key])) {
                $query->witch($key, $_GET[$key]);
            }
        }

        $perPage = max(1, (int) ($_GET['per_page'] ?? 10));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $allServers = $query->get();

        // Loop com referência (&$server) para injetarmos o 'owner_email' e 'node_name' direto nos itens retornados pelo banco
        foreach ($allServers as &$server) {
            $oId = (is_array($server) || $server instanceof \ArrayAccess) ? ($server['ownerId'] ?? null) : ($server->ownerId ?? null);
            $email = $rawOwnerEmails[$oId] ?? 'Desconhecido';

            $nId = (is_array($server) || $server instanceof \ArrayAccess) ? ($server['nodeUuid'] ?? null) : ($server->nodeUuid ?? null);
            $nodeName = $rawNodeNames[$nId] ?? 'Desconhecida';

            if (is_array($server) || $server instanceof \ArrayAccess) {
                $server['owner_email'] = $email;
                $server['node_name'] = $nodeName;
            } else {
                $server->owner_email = $email;
                $server->node_name = $nodeName;
            }
        }
        unset($server); // Limpa a referência de segurança do foreach

        $totalItems = count($allServers);

        $servers = array_slice(
            $allServers,
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
            ['label' => 'ID', 'key' => 'id', 'type' => 'text'],
            ['label' => 'Nome', 'key' => 'name', 'type' => 'text'],
            ['label' => 'Node', 'key' => 'node_name', 'type' => 'link', 'url_key' => 'nodeUuid', 'url' => '/admin/nodes/[nodeUuid]/edit'],
            ['label' => 'Dono', 'key' => 'owner_email', 'type' => 'link', 'url_key' => 'ownerId', 'url' => '/admin/users/[ownerId]/edit'],
        ];

        $viewData = [
            'resources' => $servers,
            'map' => $map,
            'see' => 'servers/[id]/edit',
            'create' => 'servers/create',
            'delete' => 'servers/[id]/delete?return=all',
            'filters' => $filters,
            'pagination' => $pagination
        ];

        return $response->view(
            'resources.view_resources',
            $this->getViewData($request, 'Servidores', $viewData)
        );
    }

    private function getServer(string $id): ?Server
    {
        return Server::find($id);
    }

    public function viewEdit(Request $request, Response $response): Response
    {
        $server = $this->getServer($request->getParam('server'));
        if (!$server) {
            return $response->view('resources.resource_not_found', ['title' => 'server']);
        }

        // Recupera os dados velhos, se houver falha
        $old = $this->getOldInput();
        if (!empty($old)) {
            // Se falhou, sobrescreve os dados do $server apenas na memória para renderizar o erro com o dado que o usuário digitou
            foreach ($old as $key => $val) {
                if ($key === 'env') {
                    $server->envVars = is_array($val) ? json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $val;
                } else {
                    $server->{$key} = $val;
                }
            }
        }

        $ownerUser = User::get('id', $server->ownerId);
        $viewData = [
            'ownerUser' => $ownerUser,
            'resource' => $server,
            'map' => $server->view_map,
            'canDelete' => true,
            'deleteUrl' => 'servers/[id]/delete?return=edit',
            'custom_delete_button' => 'partials.server.cards.delete_buttons',
            // Ao editar, o startup e env estarão inclusos dinamicamente dentro do core_docker
            'custom_tabs' => [
                ['view' => 'partials.server.cards.allocation', 'label' => 'Alocação'],
                ['view' => 'partials.server.cards.core_docker', 'label' => 'Core, Docker & Startup'],
            ],
        ];

        return $response->view('resources.edit_create', $this->getViewData($request, "Servidor - {$server->name}", $viewData));
    }

    public function edit(Request $request, Response $response): Response
    {
        $server = $this->getServer($request->getParam('server'));
        if (!$server) {
            return $response->view('resources.resource_not_found', ['title' => 'server']);
        }

        $body = $request->getBody();
        $server->name = $body['name'] ?? $server->name;
        $server->description = $body['description'] ?? $server->description;

        if (isset($body['ownerId'])) $server->ownerId = (string)$body['ownerId'];

        if (isset($body['coreId'])) $server->coreId = (string)$body['coreId'];
        if (isset($body['dockerImage'])) $server->dockerImage = (string)$body['dockerImage'];

        if (array_key_exists('maxAdditionalAllocations', $body)) {
            $rawMax = trim((string) $body['maxAdditionalAllocations']);
            $server->maxAdditionalAllocations = $rawMax === '' ? null : max(0, (int) $rawMax);
        }

        if (array_key_exists('maxDatabases', $body)) {
            $rawMaxDb = trim((string) $body['maxDatabases']);
            $server->maxDatabases = $rawMaxDb === '' ? null : max(0, (int) $rawMaxDb);
        }

        // Salva se o servidor está suspenso ou não
        if (isset($body['suspended'])) {
            $server->suspended = (int)$body['suspended'];
        }

        // Handling Allocation changes
        $newAllocationId = isset($body['allocationId']) ? (int) $body['allocationId'] : 0;

        if ($newAllocationId > 0 && $newAllocationId != $server->allocationId) {
            // Check if it's free, agora chamando a função do ServerService
            $allocation = $this->serverService->getFreeAllocationForNodeById((string) $server->nodeUuid, $newAllocationId);
            if (!$allocation) {
                $this->flashOldInput($body);
                return $response->setFlash(['error' => 'A allocation selecionada é inválida ou já está em uso'])->redirect("/admin/servers/{$server->id}/edit");
            }

            // Release old allocation
            if ($server->allocationId) {
                try {
                    $oldAlloc = Allocation::find($server->allocationId);
                    if ($oldAlloc) {
                        $oldAlloc->assignedTo = null;
                        $oldAlloc->save();
                    }
                } catch (\Throwable $e) {}
            }

            // Assign new allocation
            $server->allocationId = $allocation->id;
            try {
                $allocation->assignedTo = (string) $server->id;
                $allocation->save();
            } catch (\Throwable $e) {}
        }


        $core = $this->getCore((string)$server->coreId);
        if (!$core) {
            $this->flashOldInput($body);
            return $response->setFlash(['error' => 'Core não encontrado'])->redirect("/admin/servers/{$server->id}/edit");
        }

        // startupCommand pode ser custom, mas se vier vazio volta pro default do core
        if (isset($body['startupCommand'])) {
            $server->startupCommand = trim((string)$body['startupCommand']);
        }
        if ($server->startupCommand === null || trim((string)$server->startupCommand) === '') {
            $server->startupCommand = $core->startupCommand ?? null;
        }

        // stopCommand não é personalizável: sempre vem do core selecionado
        $server->stopCommand = $core->stopCommand ?? null;

        if (isset($body['env']) && is_array($body['env'])) {
            try {
                $env = EnvVarUtils::validateAndApplyDefaults($core->getVariables(), $body['env']);
                $server->envVars = !empty($env)
                    ? json_encode($env, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                    : null;
            } catch (\Throwable $e) {
                $this->flashOldInput($body);
                return $response->setFlash(['error' => $e->getMessage()])->redirect("/admin/servers/{$server->id}/edit");
            }
        }
        $server->ram = isset($body['ram']) ? (int)$body['ram'] : $server->ram;
        $server->cpu = isset($body['cpu']) ? (int)$body['cpu'] : $server->cpu;
        $server->disk = isset($body['disk']) ? (int)$body['disk'] : $server->disk;

        $existingMap = $server->getAdditionalAllocationsMap();

        // Chamadas auxiliares delegadas para o service
        $newFixed = $this->serverService->parseAdditionalFixed($body['additionalAllocationsFixed'] ?? null);
        $newFixed = array_values(array_diff($newFixed, [$server->allocationId]));
        $oldFixed = $existingMap['FIXED'] ?? [];
        $this->serverService->syncFixedAllocations($server, $oldFixed, $newFixed);

        $existingMap['FIXED'] = $newFixed;
        $existingMap['BYUSER'] = array_values(array_diff($existingMap['BYUSER'] ?? [], $newFixed));
        $server->additionalAllocations = (!empty($existingMap['FIXED']) || !empty($existingMap['BYUSER']))
            ? json_encode($existingMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : null;

        $server->save();

        return $response->setFlash(['success' => 'Servidor atualizado'])->redirect("/admin/servers/{$server->id}/edit");
    }

    public function delete(Request $request, Response $response): Response
    {
        $server = $this->getServer($request->getParam('server'));
        if (!$server) return $response->setFlash(['error'=>'Server não encontrado'])->redirect('/admin/servers');

        $mode = $request->getQuery()['mode'] ?? 'safe';
        $user = $request->getParsed('user');
        $userUuid = $user->id ?? $server->ownerId;

        try {
            // Lógica de deleção delegada para o service
            $this->serverService->deleteServer($server, (string) $userUuid, $mode);
            return $response->setFlash(['success' => 'Servidor excluído'])->redirect('/admin/servers');
        } catch (\Exception $e) {
            return $response->setFlash(['error' => $e->getMessage()])->redirect('/admin/servers');
        }
    }

    // --- API helpers used by partials ---
    public function apiUsersSearch(Request $request, Response $response): Response
    {
        $q = trim((string) ($request->getQuery()['email'] ?? ''));
        if ($q === '') return $response->json([]);

        $pdo = \Vatts\Database\DB::getPdo();
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` LIKE :q LIMIT 20");
        $stmt->execute(['q' => "%{$q}%"]);
        $rows = $stmt->fetchAll();

        $out = array_map(function($r){
            return [
                'id' => $r['id'] ?? null,
                'uuid' => $r['uuid'] ?? ($r['id'] ?? null),
                'email' => $r['email'] ?? null,
                'name' => $r['name'] ?? null,
            ];
        }, $rows);

        return $response->json($out);
    }

    public function apiNodesList(Request $request, Response $response): Response
    {
        $nodes = Node::all();
        $out = [];
        foreach ($nodes as $n) {
            $status = false;
            try { $st = $n->getStatus(); $status = (bool)$st; } catch (\Throwable $e) { $status = false; }
            $out[] = [
                'id' => $n->id ?? null,
                'name' => $n->name ?? null,
                'ip' => $n->ip ?? null,
                'port' => $n->port ?? null,
                'ssl' => $n->ssl ?? false,
                'online' => $status,
                'location' => $n->location ?? null,
                'token' => $n->token ?? null,
            ];
        }
        return $response->json($out);
    }

    public function apiCoresList(Request $request, Response $response): Response
    {
        $cores = Core::all();
        $out = array_map(function($c){
            return [
                'id' => $c->id ?? null,
                'name' => $c->name ?? null,
                'description' => $c->description ?? null,
                'dockerImages' => method_exists($c, 'getDockerImages') ? $c->getDockerImages() : json_decode($c->dockerImages ?? '[]', true),
                'startupCommand' => $c->startupCommand ?? '',
                'stopCommand' => $c->stopCommand ?? '',
                'variables' => method_exists($c, 'getVariables') ? $c->getVariables() : json_decode($c->variables ?? '[]', true),
            ];
        }, $cores);
        return $response->json($out);
    }

    public function apiAllocationsList(Request $request, Response $response): Response
    {
        $nodeId = trim((string) ($request->getQuery()['nodeId'] ?? ''));
        if ($nodeId === '') {
            return $response->json([]);
        }

        $serverId = trim((string) ($request->getQuery()['serverId'] ?? ''));

        $pdo = DB::getPdo();

        $query = "SELECT `id`, `ip`, `externalIp`, `port`, `assignedTo` FROM `allocations` WHERE `nodeId` = :nodeId AND (`assignedTo` IS NULL";
        $params = ['nodeId' => $nodeId];

        if ($serverId !== '') {
            $query .= " OR `assignedTo` = :serverId";
            $params['serverId'] = $serverId;
        }

        $query .= ") ORDER BY `port` ASC LIMIT 500";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $out = [];

        foreach ($rows as $r) {
            $isAssignedToMe = ($serverId !== '' && ($r['assignedTo'] ?? '') === $serverId);

            if (is_null($r['assignedTo']) || $isAssignedToMe) {
                $out[] = [
                    'id' => $r['id'] ?? null,
                    'ip' => $r['ip'] ?? null,
                    'externalIp' => $r['externalIp'] ?? null,
                    'port' => $r['port'] ?? null,
                    'isAssignedToMe' => $isAssignedToMe,
                ];
            }
        }

        return $response->json($out);
    }
}