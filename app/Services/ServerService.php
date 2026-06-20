<?php

namespace App\Services;

use models\Server;
use models\Node;
use models\Core;
use models\Allocation;
use App\Utils\EnvVarUtils;
use Vatts\Database\DB;
use App\Services\Logger; // [ESTABILIDADE] Import obrigatório adicionado para evitar Fatal Error no deleteServer()

class ServerService
{
    /**
     * Cria um novo servidor comunicando com o Node e salvando no banco de dados.
     * Ideal para ser reutilizado em rotas web e rotas de API.
     * * @throws \Exception Em caso de falha de validação ou erro de comunicação com o Node.
     */
    public function createServer(array $data): Server
    {
        $name = trim((string)($data['name'] ?? ''));
        $ownerId = trim((string)($data['ownerId'] ?? ''));
        $nodeId = trim((string)($data['nodeId'] ?? ''));
        $coreId = trim((string)($data['coreId'] ?? ''));
        $dockerImage = trim((string)($data['dockerImage'] ?? ''));
        $startupCommand = (string)($data['startupCommand'] ?? '');
        $envVars = isset($data['env']) && is_array($data['env']) ? $data['env'] : [];
        $allocationId = (int) ($data['allocationId'] ?? 0);
        $additionalFixed = $data['additionalAllocationsFixed'] ?? null;

        $maxAdditionalAllocations = trim((string) ($data['maxAdditionalAllocations'] ?? ''));
        $maxAdditionalAllocations = $maxAdditionalAllocations === '' ? null : max(0, (int) $maxAdditionalAllocations);

        $maxDatabases = trim((string) ($data['maxDatabases'] ?? ''));
        $maxDatabases = $maxDatabases === '' ? null : max(0, (int) $maxDatabases);

        if ($name === '' || $ownerId === '' || $nodeId === '' || $coreId === '') {
            throw new \Exception('Campos obrigatórios ausentes (name, ownerId, nodeId, coreId)');
        }

        if ($allocationId <= 0) {
            throw new \Exception('Selecione uma allocation (porta) para o servidor');
        }

        $node = Node::find($nodeId);
        if (!$node) {
            throw new \Exception('Node não encontrado');
        }

        $core = Core::find($coreId);
        if (!$core) {
            throw new \Exception('Core não encontrado');
        }

        // Se a dockerImage vier em branco, pega a primeira disponível no Core
        if ($dockerImage === '' || !$dockerImage) {
            $coreImages = json_decode($core->dockerImages ?? '[]', true);
            if (is_array($coreImages) && !empty($coreImages) && isset($coreImages[0]['image'])) {
                $dockerImage = $coreImages[0]['image'];
            }
        }

        // aplica defaults e valida as env vars conforme regras do core
        try {
            $envVars = EnvVarUtils::validateAndApplyDefaults($core->getVariables(), $envVars);
        } catch (\Throwable $e) {
            // [SEGURANÇA] Information Disclosure: Impede o vazamento de stack traces e dados internos
            // do parser de variáveis de ambiente para o usuário final.
            Logger::error("EnvVar Validation Error: " . $e->getMessage());
            throw new \Exception("Configuração de variáveis de ambiente inválida.");
        }

        // allocation escolhida pelo usuário (precisa estar livre e pertencer ao node)
        $allocation = $this->getFreeAllocationForNodeById((string) $node->id, $allocationId);
        if (!$allocation) {
            throw new \Exception('Allocation inválida ou já está em uso');
        }

        // Gera um server UUID local e usa como serverId para enviar ao node
        $serverUuid = $this->generateUuid();

        // [SEGURANÇA] Prevenção de SSRF e malformações
        // Valida se o URL do Node é de fato um URL HTTP/HTTPS válido antes de disparar o cURL
        $baseUrl = $node->getUrl();
        if (!filter_var($baseUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $baseUrl)) {
            throw new \Exception('URL do Node configurada incorretamente.');
        }

        $url = rtrim($baseUrl, '/') . '/api/v1/servers/create';

        $payload = json_encode([
            'token' => $node->token,
            'serverId' => $serverUuid,
            'userUuid' => $ownerId,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen($payload)]);

        // CORREÇÃO: Aumentado o tempo de timeout para 60 segundos
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        if ((int)($node->httpsConnection ?? 0) === 1) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        $resp = curl_exec($ch);
        $curlError = curl_error($ch); // Captura a mensagem de erro exata do cURL
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($resp === false) {
            // [SEGURANÇA] Information Disclosure de Topologia de Rede
            // O erro nativo do cURL ($curlError) vaza IPs internos, portas e razões de bloqueio de firewall.
            // Logamos internamente e mostramos uma mensagem cega pro usuário.
            Logger::error("cURL Error [Node {$node->id}]: {$curlError}");
            throw new \Exception("Erro de comunicação com o node de hospedagem.");
        }

        $responseData = json_decode($resp, true);

        if ($httpCode !== 200 || ($responseData !== null && isset($responseData['error']))) {
            $msg = $responseData['error'] ?? 'Erro desconhecido ao criar servidor no node';
            // [SEGURANÇA] Evita XSS / Forjamento provindos da API do Node
            // Se um Node for comprometido e retornar HTML na chave error, o strip_tags neutraliza o ataque.
            $safeMsg = strip_tags((string) $msg);
            throw new \Exception("Node API: {$safeMsg}");
        }

        // Se chegou aqui é sucesso conforme espec. Agora salva no banco
        $server = new Server();
        $server->name = $name;
        $server->description = $data['description'] ?? '';
        $server->ownerId = $ownerId;
        $server->ram = (int)($data['ram'] ?? 1024);
        $server->cpu = (int)($data['cpu'] ?? 10);
        $server->disk = (int)($data['disk'] ?? 2048);
        $server->coreId = $coreId;
        // Armazena referência ao node (usando id para simplicidade)
        $server->nodeUuid = (string)$node->id;
        $server->dockerImage = $dockerImage !== '' ? $dockerImage : null;
        $server->startupCommand = trim($startupCommand) !== '' ? trim($startupCommand) : ($core->startupCommand ?? null);
        $server->envVars = !empty($envVars) ? json_encode($envVars, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : null;
        $server->serverUuid = $serverUuid;
        $server->maxAdditionalAllocations = $maxAdditionalAllocations;
        $server->maxDatabases = $maxDatabases;

        // salva a allocation escolhida
        $server->allocationId = $allocation->id ?? null;

        $fixedIds = $this->parseAdditionalFixed($additionalFixed);
        $fixedIds = array_values(array_diff($fixedIds, [$server->allocationId]));
        $server->additionalAllocations = !empty($fixedIds)
            ? json_encode(['FIXED' => $fixedIds, 'BYUSER' => []], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : null;

        $server->save();

        // marca allocation como atribuída ao server
        try {
            $allocation->assignedTo = (string) $server->id;
            $allocation->save();
        } catch (\Throwable $e) {
            // não bloqueia o fluxo, mas deixa rastreável
            Logger::error("Falha ao salvar a atribuicao da allocation {$allocation->id}: " . $e->getMessage());
        }

        $this->syncFixedAllocations($server, [], $fixedIds);


        $server->sendAction('install', null);

        return $server;
    }

    /**
     * Deleta o servidor, comunicando com o Node e liberando os recursos no banco local.
     */
    public function deleteServer(Server $server, string $userUuid, string $mode = 'safe'): void
    {
        if ($mode !== 'force') {
            $result = $this->requestDeleteOnNode($server, $userUuid);
            if ($result !== true) {
                $msg = is_string($result) && $result !== '' ? $result : 'Falha ao deletar no node.';
                $safeMsg = strip_tags($msg); // Segurança adicional
                throw new \Exception($safeMsg);
            }
        } else {
            $this->requestDeleteOnNode($server, $userUuid);
        }

        // libera allocations atribuídas a esse server
        try {
            $allocs = Allocation::where("assignedTo", $server->id);
            foreach ($allocs as $a) {
                unset($a->view_map);
                $a->assignedTo = null;
                $a->save();
            }
        } catch (\Throwable $e) {
            Logger::error($e);
        }

        $server->delete();
    }

    public function getFreeAllocationForNodeById(string $nodeId, int $allocationId): ?Allocation
    {
        $pdo = DB::getPdo();
        // Utiliza parâmetros nomeados blindando o PDO contra Injeções de SQL.
        $stmt = $pdo->prepare("SELECT * FROM `allocations` WHERE `id` = :id AND `nodeId` = :nodeId AND (`assignedTo` IS NULL OR `assignedTo` = '') LIMIT 1");
        $stmt->execute([
            'id' => $allocationId,
            'nodeId' => $nodeId,
        ]);
        $row = $stmt->fetch();
        return $row ? new Allocation($row) : null;
    }

    public function parseAdditionalFixed($value): array
    {
        if ($value === null) return [];

        if (is_array($value)) {
            $items = $value;
        } else {
            $decoded = json_decode((string) $value, true);
            $items = is_array($decoded) ? $decoded : [];
        }

        $out = [];
        foreach ($items as $item) {
            $id = (int) $item;
            if ($id > 0) $out[] = $id;
        }

        return array_values(array_unique($out));
    }

    public function syncFixedAllocations(Server $server, array $oldFixed, array $newFixed): void
    {
        $nodeId = (string) $server->nodeUuid;
        $removed = array_values(array_diff($oldFixed, $newFixed));
        $added = array_values(array_diff($newFixed, $oldFixed));

        foreach ($removed as $allocId) {
            $alloc = Allocation::find((int) $allocId);
            if (!$alloc) continue;
            if ((string) $alloc->nodeId !== $nodeId) continue;
            if ((string) $alloc->assignedTo !== (string) $server->id) continue;
            if ((int) $alloc->id === (int) $server->allocationId) continue;
            $alloc->assignedTo = null;
            $alloc->save();
        }

        foreach ($added as $allocId) {
            $alloc = Allocation::find((int) $allocId);
            if (!$alloc) continue;
            if ((string) $alloc->nodeId !== $nodeId) continue;
            if (!empty($alloc->assignedTo) && (string) $alloc->assignedTo !== (string) $server->id) continue;
            if ((int) $alloc->id === (int) $server->allocationId) continue;
            $alloc->assignedTo = (string) $server->id;
            $alloc->save();
        }
    }

    private function generateUuid(): string
    {
        // simples UUID v4 gerado em PHP
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function requestDeleteOnNode(Server $server, string $userUuid): bool|string
    {
        try {
            $node = Node::find($server->nodeUuid);
            if (!$node) return 'Node associado ao servidor não encontrado.';

            $response = $node->apiRequest('POST', '/api/v1/servers/delete', [
                'serverId' => $server->serverUuid,
                'userUuid' => $userUuid,
            ]);

            // Verifica se a resposta é um array e se o 'success' é verdadeiro
            if (is_array($response) && isset($response['success']) && $response['success']) {
                return true;
            }

            // Retorna a mensagem de erro da API ou uma mensagem padrão caso a requisição falhe
            if (is_array($response) && isset($response['body'])) {
                return is_string($response['body']) ? $response['body'] : json_encode($response['body']);
            }

            return 'Falha ao comunicar com o Node.';
        } catch (\Throwable $e) {
            // [SEGURANÇA] Proteção contra Information Disclosure
            // Logamos internamente e retornamos erro genérico
            Logger::error("Request Delete On Node Error: " . $e->getMessage());
            return 'Ocorreu um erro interno de conexão.';
        }
    }
}