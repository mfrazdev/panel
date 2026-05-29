<?php

namespace models;

use App\Services\Logger;
use Vatts\Database\Model;
use Vatts\Database\DB;

class Server extends Model
{
    protected static ?string $table = 'servers';

    // Mapeamento para renderização automática no Painel
    public array $view_map = [
        'Informações Básicas' => [
            ['label' => 'Nome do Servidor', 'key' => 'name', 'type' => 'text', 'desc' => 'Nome de exibição do servidor.'],
            ['label' => 'Descrição', 'key' => 'description', 'type' => 'textarea', 'desc' => 'Breve descrição do servidor.'],
            ['label' => 'Servidor Suspenso', 'key' => 'suspended', 'type' => 'select', 'options' => [0 => 'Não', 1 => 'Sim'], 'desc' => 'Se sim, o servidor não poderá ser iniciado, modificado ou receber comandos.'],
            ['label' => 'Dono (Owner ID)', 'key' => 'ownerId', 'type' => 'text', 'desc' => 'UUID do usuário proprietário.', 'form' => false],
            ['label' => 'Grupo', 'key' => 'group', 'type' => 'text', 'desc' => 'Grupo organizacional do servidor.'],
        ],

        'Recursos de Hardware' => [
            ['label' => 'Memória RAM (MB)', 'key' => 'ram', 'type' => 'number', 'desc' => 'Limite de memória RAM.'],
            ['label' => 'CPU (%)', 'key' => 'cpu', 'type' => 'number', 'desc' => 'Limite de processamento.'],
            ['label' => 'Disco (MB)', 'key' => 'disk', 'type' => 'number', 'desc' => 'Limite de armazenamento.'],
        ],
        'Cotas' => [
            ['label' => 'Máx. Alocações Adicionais do Usuário', 'key' => 'maxAdditionalAllocations', 'type' => 'number', 'desc' => 'Quantidade máxima de alocações adicionais que o usuário pode adicionar sozinho. Deixe vazio para ilimitado.'],
            ['label' => 'Máx. Bancos de Dados', 'key' => 'maxDatabases', 'type' => 'number', 'desc' => 'Quantidade máxima de bancos de dados que este servidor pode ter. Deixe vazio para ilimitado.'],
        ],
    ];

    public static array $schema = [
        'id'          => 'id',
        'name'        => 'string',
        'description' => 'string',
        'ownerId'     => 'string',
        'ram'         => 'int',
        'cpu'         => 'int',
        'disk'        => 'int',
        'coreId'      => 'string',
        'nodeUuid'    => 'string',
        'dockerImage' => 'string',
        'startupCommand' => 'text',
        'envVars'     => 'text',
        'group'       => 'string',
        'serverUuid'  => 'string',
        'additionalAllocations' => 'text',
        'maxAdditionalAllocations' => 'int',
        'databases'   => 'text',
        'maxDatabases'=> 'int',
        'suspended'   => 'int',
        'schedulers'  => 'text', // NOVO CAMPO: Para armazenar os agendamentos em JSON

        'allocationId' => 'foreign:allocations.id',
    ];

    // Propriedades Obrigatórias
    public int $id;
    public string $name;
    public string $ownerId;
    public int $ram;
    public int $cpu;
    public int $disk;
    public string $coreId;
    public string $nodeUuid;
    public ?int $suspended = 0;

    // Propriedades Opcionais
    public ?string $description = null;
    public ?string $dockerImage = null;
    public ?string $group = null;
    public ?string $startupCommand = null;
    public ?string $envVars = null;
    public ?string $additionalAllocations = null;
    public ?int $maxAdditionalAllocations = null;
    public ?string $databases = null;
    public ?int $maxDatabases = null;
    public ?string $schedulers = null; // NOVO CAMPO

    // Allocation (definida na criação)
    public ?int $allocationId = null;
    // UUID do servidor (gerado antes do envio para o node ou retornado pelo node)
    public ?string $serverUuid = null;

    // ==========================================
    // MÉTODOS DE BANCO DE DADOS
    // ==========================================
    public function getDatabasesList(): array
    {
        $data = json_decode($this->databases ?? '[]', true);
        return is_array($data) ? $data : [];
    }

    public function addDatabase(array $dbInfo): void
    {
        $dbs = $this->getDatabasesList();
        $dbs[] = $dbInfo;
        $this->databases = json_encode($dbs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->save();

    }

    public function removeDatabase(string $dbName): void
    {
        $dbs = $this->getDatabasesList();
        $dbs = array_filter($dbs, function($db) use ($dbName) {
            return $db['dbName'] !== $dbName;
        });
        $this->databases = json_encode(array_values($dbs), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->save();
    }

    // ==========================================
    // MÉTODOS DE SCHEDULERS (AGENDAMENTOS)
    // ==========================================
    public function getSchedulersList(): array
    {
        $data = json_decode($this->schedulers ?? '[]', true);
        return is_array($data) ? $data : [];
    }

    public function addScheduler(string $name, string $cron, array $tasks, bool $isActive = true): string
    {
        $schedulers = $this->getSchedulersList();
        $id = uniqid('sched_'); // Gera um ID único para a task

        $schedulers[] = [
            'id' => $id,
            'name' => $name,
            'cron' => $cron, // Ex: "*/5 * * * *"
            'active' => $isActive,
            'tasks' => $tasks // Array de tasks. Ex: [['type' => 'action', 'payload' => 'restart'], ['type' => 'command', 'payload' => 'say Oi']]
        ];

        $this->schedulers = json_encode($schedulers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->save();

        return $id;
    }

    public function removeScheduler(string $schedulerId): void
    {
        $schedulers = $this->getSchedulersList();
        $schedulers = array_filter($schedulers, function($sched) use ($schedulerId) {
            return $sched['id'] !== $schedulerId;
        });

        $this->schedulers = json_encode(array_values($schedulers), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->save();
    }

    public function toggleScheduler(string $schedulerId, bool $status): void
    {
        $schedulers = $this->getSchedulersList();
        foreach ($schedulers as &$sched) {
            if ($sched['id'] === $schedulerId) {
                $sched['active'] = $status;
                break;
            }
        }
        $this->schedulers = json_encode($schedulers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->save();
    }

    public function editScheduler(string $schedulerId, string $name, string $cron, array $tasks, bool $isActive): bool
    {
        $schedulers = $this->getSchedulersList();
        $updated = false;

        foreach ($schedulers as &$sched) {
            if ($sched['id'] === $schedulerId) {
                $sched['name'] = $name;
                $sched['cron'] = $cron;
                $sched['tasks'] = $tasks;
                $sched['active'] = $isActive;
                $updated = true;
                break;
            }
        }

        if ($updated) {
            $this->schedulers = json_encode($schedulers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $this->save();
        }

        return $updated;
    }

    // ==========================================
    // OUTROS MÉTODOS
    // ==========================================
    public function getAdditionalAllocationsMap(): array
    {
        $data = json_decode($this->additionalAllocations ?? '', true);
        if (!is_array($data)) {
            $data = [];
        }

        $normalizeList = function ($value): array {
            $items = is_array($value) ? $value : [$value];
            $out = [];
            foreach ($items as $item) {
                if ($item === null || $item === '') continue;
                $id = (int) $item;
                if ($id > 0) $out[] = $id;
            }
            return $out;
        };

        $fixed = [];
        $byUser = [];

        if (array_key_exists('FIXED', $data) || array_key_exists('BYUSER', $data)) {
            $fixed = $normalizeList($data['FIXED'] ?? []);
            $byUser = $normalizeList($data['BYUSER'] ?? []);
        } else {
            foreach ($data as $key => $value) {
                $type = null;
                $ids = [];

                if (is_string($key) && in_array(strtoupper($key), ['FIXED', 'BYUSER'], true)) {
                    $type = strtoupper($key);
                    $ids = $normalizeList($value);
                } elseif (is_string($value) && in_array(strtoupper($value), ['FIXED', 'BYUSER'], true)) {
                    $type = strtoupper($value);
                    $ids = $normalizeList($key);
                }

                if ($type === 'FIXED') {
                    $fixed = array_merge($fixed, $ids);
                } elseif ($type === 'BYUSER') {
                    $byUser = array_merge($byUser, $ids);
                }
            }
        }

        $fixed = array_values(array_unique($fixed));
        $byUser = array_values(array_diff(array_unique($byUser), $fixed));

        return [
            'FIXED' => $fixed,
            'BYUSER' => $byUser,
        ];
    }

    public function getFirstAllocation(): Allocation
    {
        return Allocation::get('id', $this->allocationId);
    }

    public static function getByShortUuid(string $short): ?self
    {
        $pdo = DB::getPdo();
        $table = self::getTableName();
        $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE `serverUuid` LIKE :p LIMIT 1");
        $stmt->execute(['p' => $short . '-%']);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? new self($row, false) : null;
    }

    public function getNode(): Node
    {
        return Node::get('id', $this->nodeUuid);
    }

    public function getStatus(?string $requestUserUuid = null): array|bool
    {
        if ($this->suspended === 1) {
            return [
                'status' => 'suspended',
                'usage'  => null
            ];
        }

        $node = $this->getNode();

        if (!$node) {
            return false;
        }

        $payload = [
            'serverId' => $this->serverUuid,
            'userUuid' => $requestUserUuid ?? $this->ownerId
        ];

        $request2 = $node->apiRequest("POST", '/api/v1/servers/usage', $payload);

        if (!$request2 || empty($request2['success'])) {
            return false;
        }


        return [
            'status' => $request2['body']['usage']["state"] ?? 'unknown',
            'usage'  => $request2['body']['usage'] ?? null
        ];
    }

    public function hasPermission(User $user): bool
    {
        return $this->ownerId === (string) $user->id || $user->isAdmin();
    }

    public function sendAction(string $action, ?string $requestUserUuid = null, ?string $command = null): array|bool
    {
        // Bloqueia ações de inicialização, instalação ou comandos caso o servidor esteja suspenso
        if ($this->suspended === 1 && in_array($action, ['start', 'restart', 'install', 'command'])) {
            try {
                $logError = new ServerAuditLog();
                $logError->server_id = $this->id;
                $logError->user_id = null; // Ajuste conforme necessário
                $logError->action = "Falha ao executar ação '{$action}' - Servidor Suspenso";
                $logError->ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $logError->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                $logError->save();
            } catch (\Exception $e) {
                Logger::error("Erro ao salvar log de auditoria (falha): " . $e->getMessage());
            }
            Logger::error("Ação bloqueada: Servidor {$this->id} está suspenso.");
            return false;
        }

        $timeStart = microtime(true);
        try {
            $payload = [
                'serverId' => $this->serverUuid,
                'userUuid' => $requestUserUuid ?? $this->ownerId,
                'action'   => $action,
                'disk' => $this->disk,
            ];

            $pdo = DB::getPdo();

            switch ($action) {
                case 'install':
                case 'start':
                case 'restart':
                    $stmt = $pdo->prepare("
                    SELECT 
                        c.id as core_id, c.name as core_name, 
                        c.startupCommand as core_startup, c.stopCommand as core_stop, 
                        c.startupScript,
                        c.dockerEntrypoint,
                        c.installScript,
                        c.installImage,
                        c.installEntrypoint,
                        c.startupParser, c.configSystem,
                        c.rootAcess,
                        c.maintainable,
                        c.variables,
                        a.ip, a.port, a.externalIp
                    FROM `cores` c
                    LEFT JOIN `allocations` a ON a.id = :allocId
                    WHERE c.id = :coreId
                    LIMIT 1
                ");
                    $stmt->execute([
                        'allocId' => $this->allocationId,
                        'coreId'  => $this->coreId
                    ]);
                    $related = $stmt->fetch(\PDO::FETCH_ASSOC);

                    $payload['memory'] = (int) $this->ram;
                    $payload['cpu'] = (int) $this->cpu;
                    $payload['disk'] = (int) $this->disk;
                    $payload['image'] = $this->dockerImage;
                    $payload['additionalAllocation'] = [];

                    $additionalMap = $this->getAdditionalAllocationsMap();
                    $additionalIds = array_values(array_unique(array_merge($additionalMap['FIXED'], $additionalMap['BYUSER'])));
                    if ($this->allocationId) {
                        $additionalIds = array_values(array_diff($additionalIds, [$this->allocationId]));
                    }

                    if (!empty($additionalIds)) {
                        $placeholders = implode(',', array_fill(0, count($additionalIds), '?'));
                        $stmtAlloc = $pdo->prepare("SELECT `id`, `ip`, `externalIp`, `port` FROM `allocations` WHERE `id` IN ({$placeholders})");
                        $stmtAlloc->execute($additionalIds);
                        $rows = $stmtAlloc->fetchAll(\PDO::FETCH_ASSOC);

                        $payload['additionalAllocation'] = array_map(function ($row) {
                            return [
                                'id' => (int) ($row['id'] ?? 0),
                                'ip' => $row['ip'] ?? null,
                                'port' => (int) ($row['port'] ?? 0),
                                'externalIp' => $row['externalIp'] ?? null,
                            ];
                        }, $rows);
                    }

                    $serverEnv = json_decode($this->envVars ?? '{}', true) ?: [];
                    $finalEnv = [];

                    if ($related && !empty($related['variables'])) {
                        $coreVars = json_decode($related['variables'], true) ?: [];

                        foreach ($coreVars as $cv) {
                            $envName = $cv['envVariable'] ?? null;
                            $rules = $cv['rules'] ?? '';

                            if (!$envName) continue;

                            $default = '';
                            $rulesArray = explode('|', $rules);
                            foreach ($rulesArray as $rule) {
                                if (str_starts_with($rule, 'default:')) {
                                    $default = substr($rule, 8);
                                    break;
                                }
                            }

                            $finalEnv[$envName] = $default;
                        }
                    }

                    $payload['environment'] = array_merge($finalEnv, $serverEnv);

                    if ($related) {
                        $payload['primaryAllocation'] = $related['ip'] ? [
                            'ip'         => $related['ip'],
                            'port'       => $related['port'],
                            'externalIp' => $related['externalIp']
                        ] : null;

                        $payload['core'] = [
                            'id'             => $related['core_id'],
                            'name'           => $related['core_name'],
                            'startupCommand' => !empty($this->startupCommand) ? $this->startupCommand : $related['core_startup'],
                            'startupScript'  => $related['startupScript'] ?? null,
                            'dockerEntrypoint' => $related['dockerEntrypoint'] ?? null,
                            'stopCommand'    => $related['core_stop'],
                            'startupParser'  => json_decode($related['startupParser'] ?? '{}', true),
                            'configSystem'   => json_decode($related['configSystem'] ?? '{}', true),
                            'installScript' => $related["installScript"] ?? null,
                            'installImage' => $related["installImage"] ?? null,
                            'installEntrypoint' => $related['installEntrypoint'] ?? null,
                            'rootAcess' => $related['rootAcess'] ?? null,
                            'maintainable' => $related['maintainable'] ?? null
                        ];
                    } else {
                        $payload['primaryAllocation'] = null;
                        $payload['core'] = null;
                    }
                    break;

                case 'stop':
                    $stmt = $pdo->prepare("SELECT `stopCommand` FROM `cores` WHERE `id` = :id LIMIT 1");
                    $stmt->execute(['id' => $this->coreId]);
                    $cmd = $stmt->fetchColumn() ?: 'stop';
                    $payload['command'] = $cmd;
                    break;

                case 'command':
                    $payload['command'] = $command;
                    break;

                case 'kill':
                    break;

                default:
                    return false;
            }

            $stmtNode = $pdo->prepare("SELECT * FROM `nodes` WHERE `id` = :id LIMIT 1");
            $stmtNode->execute(['id' => $this->nodeUuid]);
            $nodeRow = $stmtNode->fetch(\PDO::FETCH_ASSOC);

            if (!$nodeRow) return false;

            $node = new Node($nodeRow, false);


            $request = $node->apiRequest("POST", '/api/v1/servers/action', $payload);
            if($action === 'command') {
                Logger::info("[sendAction] Enviando comando para o servidor #{$this->id}: " . $command);
            }



            $nomesAcoes = [
                'start'   => 'Iniciou o servidor',
                'stop'    => 'Parou o servidor',
                'restart' => 'Reiniciou o servidor',
                'kill'    => 'Forçou a parada do servidor',
                'install' => 'Iniciou a instalação do servidor',
                'command' => $command ? "Enviou o comando: {$command}" : 'Enviou um comando',
            ];

            $acaoFormatada = $nomesAcoes[$action] ?? "Ação executada: {$action}";

            if (!$request || empty($request['success'])) {

                try {
                    $logError = new ServerAuditLog();
                    $logError->server_id = $this->id;
                    $logError->user_id = $requestUserUuid ?? null; // Ajuste conforme necessário
                    $logError->action = "Falha: " . $acaoFormatada;
                    $logError->ip = $_SERVER['REMOTE_ADDR'] ?? null;
                    $logError->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                    $logError->save();
                } catch (\Exception $e) {
                    Logger::error("Erro ao salvar log de auditoria (falha): " . $e->getMessage());
                }

                if (isset($request['body'])) {
                    Logger::error("Daemon API Error: " . json_encode($request));
                }

                return false;
            }
            // add
            try {
                $log = new ServerAuditLog();
                $log->server_id = $this->id;
                $log->user_id = $requestUserUuid ?? null; // Ajuste conforme necessário
                $log->action = $acaoFormatada;
                $log->ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $log->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                $log->save();
            } catch (\Exception $e) {
                Logger::error("Erro ao salvar log de auditoria (sucesso): " . $e->getMessage());
            }
            return $request['body'] ?? true;
        } catch (\Exception $e) {
            Logger::error("Error sending action to node: " . $e->getMessage());
            return false;
        }
    }

    public function getOwnerNameAndEmail()
    {
        try {
            $pdo = DB::getPdo();
            $stmt = $pdo->prepare("SELECT `id`, `first_name`, `email` FROM `users` WHERE `id` = :id LIMIT 1");
            $stmt->execute(['id' => $this->ownerId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            Logger::error("Error fetching owner info: " . $e->getMessage());
            return null;
        }
    }


}