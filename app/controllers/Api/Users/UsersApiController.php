<?php

namespace App\controllers\Api\Users;

use App\Services\SMTPService;
use eftec\bladeone\BladeOne;
use models\Codes;
use models\Core;
use models\Tokens;
use models\User;
use models\Allocation;
use Vatts\Database\DB;
use Vatts\Router\Request;
use Vatts\Router\Response;
use Vatts\Utils\BladeConfig;
use Vatts\Vatts;

class UsersApiController
{

    static function GenerateRandomString(int $qntd): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $string = '';

        for ($i = 0; $i < $qntd; $i++) {
            $string .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $string;
    }


    public static function verifyCode(Request $request, Response $response): Response
    {
        $code = trim($request->getQuery()['code'] ?? '');
        if ($code === '') {
            return $response->json(['error' => 'O código é obrigatório.'])->status(400);
        }
        $codedb = Codes::get('code', $code);
        if (!$codedb) {
            return $response->json(['error' => 'Código inválido ou expirado.'])->status(400);
        }
        $user = User::get($codedb->userId);

        if(!$user) {
            return $response->json(['error' => 'Usuário associado ao código não encontrado.'])->status(404);
        }

        return $response->json(['success' => true, 'email' => $codedb->email, 'userName' => $user->first_name . ' ' . $user->last_name]);
    }

    public static function changePassword(Request $request, Response $response): Response
    {
        $code = trim($request->getQuery()['code'] ?? '');
        $password = trim($request->getBody()['password'] ?? '');
        $confirmPassword = trim($request->getBody()['confirmPassword'] ?? '');

        if ($code === '') {
            return $response->json(['error' => 'O código é obrigatório.'])->status(400);
        }
        if ($password === '' || $confirmPassword === '') {
            return $response->json(['error' => 'Os campos de senha são obrigatórios.'])->status(400);
        }
        if ($password !== $confirmPassword) {
            return $response->json(['error' => 'As senhas não coincidem.'])->status(400);
        }
        if (mb_strlen($password) < 8) {
            return $response->json(['error' => 'A senha deve ter no mínimo 8 caracteres.'])->status(400);
        }
        $codedb = Codes::get('code', $code);
        if (!$codedb) {
            return $response->json(['error' => 'Código inválido ou expirado.'])->status(400);
        }
        $user = User::find($codedb->userId);
        if (!$user) {
            return $response->json(['error' => 'Usuário associado ao código não encontrado.'])->status(404);
        }
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->save();
        $codedb->delete();
        return $response->json(['success' => true]);
    }

    public static function sendRecoveryEmail(Request $request, Response $response): Response
    {
        $email = trim($request->getBody()['email'] ?? '');

        if ($email === '') {
            return $response->json(['error' => 'O campo de e-mail é obrigatório.'])->status(400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $response->json(['error' => 'E-mail inválido.'])->status(400);
        }

        $user = User::get('email', $email);
        if (!$user) {
            // Para evitar revelar se o e-mail existe ou não, retornamos sucesso mesmo que o usuário não seja encontrado
            return $response->json(['success' => true]);
        }

        // ========================================================================
        // BLOCO 1: Lógica do Banco de Dados (Antes de desconectar)
        // ========================================================================
        try {
            $code = self::generateRandomString(64);
            $token = new Codes();
            $token->userId = $user->id;
            $token->email = $email;
            $token->code = $code;
            $token->save();
        } catch (\Exception $e) {
            error_log("Erro ao criar código de recuperação no DB para {$email}: " . $e->getMessage());
            return $response->json(['error' => 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente mais tarde.'])->status(500);
        }

        // 1. Prepara a resposta de sucesso e define no objeto Response
        $response->json(['success' => true]);

        // 2. Emite a resposta HTTP e DESCONECTA o cliente (O browser vai achar que já acabou)
        self::emitAndDisconnect($response);

        // ========================================================================
        // BLOCO 2: INÍCIO DO PROCESSAMENTO EM BACKGROUND (Depois de desconectar)
        // ========================================================================
        try {
            $smtpService = new SMTPService();

            // Monta o e-mail
            $linkRecuperacao = Vatts::getEnv('URL') . "/auth/recovery?code={$code}";
            $assunto = "Recuperação de Senha";

            $corpo = BladeConfig::get()->run("emails.recovery", [
                "url" => $linkRecuperacao,
                "user" => $user
            ]);

            // Envia o e-mail
            $smtpService->send($user->email, $assunto, $corpo);

        } catch (\Exception $e) {
            // Como o cliente já foi desconectado, não podemos usar "return $response".
            // Apenas logamos o erro para debugar depois!
            error_log("Erro no background (Email/Blade) para {$email}: " . $e->getMessage());
        }

        // 4. Mata o script para evitar que o router tente enviar a Response de novo ao finalizar
        exit;
    }

    /**
     * Força o envio dos headers e do corpo para o cliente,
     * encerrando a conexão HTTP, mas mantendo o script rodando.
     */
    private static function emitAndDisconnect(Response $response): void
    {
        ignore_user_abort(true);
        set_time_limit(0);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $body = (string) $response->getBody();

        $response->header('Connection', 'close');
        $response->header('Content-Length', (string) strlen($body));

        http_response_code($response->getStatus());

        foreach ($response->getHeaders() as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    header(sprintf('%s: %s', $key, $v), false);
                }
            } else {
                header(sprintf('%s: %s', $key, $value));
            }
        }

        echo $body;

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            ob_flush();
            flush();
        }

        if (session_id()) {
            session_write_close();
        }
    }





    public static function saveNameAndDesc(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof \models\Server)) {
            return $response->json([
                'error' => 'Servidor inválido.'
            ])->status(400);
        }

        $body = $request->getBody();
        $name = trim($body["name"] ?? '');
        $desc = trim($body["desc"] ?? '');
        $group = trim($body["group"] ?? '');
        $group = $group === '' ? null : $group;

        // ===== VALIDAÇÃO DO NOME =====
        if ($name === '') {
            return $response->json(['error' => 'O nome é obrigatório.'])->status(400);
        }

        if (mb_strlen($name) > 20) {
            return $response->json(['error' => 'O nome deve ter no máximo 20 caracteres.'])->status(400);
        }

        if (!preg_match('/^[a-zA-Z0-9 |\-]+$/', $name)) {
            return $response->json([
                'error' => 'O nome só pode conter letras, números, espaços, "|" e "-".'
            ])->status(400);
        }

        // ===== VALIDAÇÃO DA DESCRIÇÃO =====
        if (mb_strlen($desc) > 200) {
            return $response->json([
                'error' => 'A descrição deve ter no máximo 200 caracteres.'
            ])->status(400);
        }

        if ($desc !== '' && preg_match('/^\s+$/', $desc)) {
            return $response->json([
                'error' => 'A descrição não pode conter apenas espaços.'
            ])->status(400);
        }

        // ===== VALIDAÇÃO DO GROUP =====
        if ($group !== null) {
            if (mb_strlen($group) > 12) {
                return $response->json([
                    'error' => 'O grupo deve ter no máximo 12 caracteres.'
                ])->status(400);
            }

            if (!preg_match('/^[A-Za-zÀ-ÿ\s]+$/', $group)) {
                return $response->json([
                    'error' => 'O grupo só pode conter letras e espaços.'
                ])->status(400);
            }

            if (preg_match('/^\s+$/', $group)) {
                return $response->json([
                    'error' => 'O grupo não pode conter apenas espaços.'
                ])->status(400);
            }
        }

        // ===== SALVAR =====
        $server->name = $name;
        $server->description = $desc;
        $server->group = $group ?? ''; // <-- aqui
        $server->save();

        return $response->json([
            "success" => true
        ]);
    }


    public static function getServer(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof \models\Server)) {
            return $response->json([
                'error' => 'Invalid server.'
            ])->status(400);
        }
        unset($server->view_map);
        $allocation = $server->getFirstAllocation();
        unset($allocation->view_map);
        $node = $server->getNode();
        return $response->json([
            'server' => $server,
            'nodeUrl' => $node->getUrl(),
            'nodeIp' => $node->ip,
            'nodeSftp' => $node->sftp,
            'allocation' => $allocation
        ]);
    }

    public static function sendAction(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof \models\Server)) {
            return $response->json([
                'error' => 'Invalid server.'
            ])->status(400);
        }
        $action = $request->getBody()['action'] ?? null;
        if (!in_array($action, ['start', 'restart', 'stop', 'kill', 'command', 'install'])) {
            return $response->json([
                'error' => 'Invalid action. Allowed actions are: start, restart, stop, kill, command, install.'
            ])->status(400);
        }
        $result = $server->sendAction($action, $request->getParsed('user')->id);
        if ($result === false) {
            return $response->json([
                'error' => 'Failed to send action. Node might be offline or unreachable.'
            ])->status(503);
        }
        return $response->json([
            'success' => true,
            'result' => $result
        ]);
    }

    public static function getStatus(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof \models\Server)) {
            return $response->json([
                'error' => 'Invalid server.'
            ])->status(400);
        }

        $status = $server->getStatus();
        if ($status === false) {
            return $response->json([
                'error' => 'Node is offline or unreachable.'
            ])->status(503);
        }

        return $response->json([
            'status' => $status
        ]);
    }


    public static function getServers(Request $request, Response $response): Response
    {
        try {
            $user = $request->getParsed('user');
            if ($user instanceof User) {
                $servers = array_map(function ($server) {
                    unset($server->view_map);
                    $allocation = $server->getFirstAllocation()->toArray();
                    unset($allocation["view_map"]);
                    $server->allocation = $allocation;
                    return $server;
                }, $user->getServers());
                $type = $request->getQuery()['type'] ?? 'not_set';



                if($user->isAdmin() && $type === 'others') {
                    $servers = $user->getOthersServers();
                }

                return $response->json([
                    'servers' => $servers
                ]);
            }
            return $response->json([
                'error' => 'Invalid user.'
            ]) ->status(400);
        } catch (\Exception $e) {
            error_log($e);
            return $response->json([
                'error' => 'An error occurred while fetching servers.',
                'details' => $e->getMessage()
            ])->status(500);
        }
    }

    public static function getAdditionalAllocations(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof \models\Server)) {
            return $response->json([
                'error' => 'Invalid server.'
            ])->status(400);
        }

        $payload = self::buildAdditionalAllocationsPayload($server);
        return $response->json($payload);
    }

    public static function addAdditionalAllocation(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof \models\Server)) {
            return $response->json([
                'error' => 'Invalid server.'
            ])->status(400);
        }

        $allocationId = (int) ($request->getBody()['allocationId'] ?? 0);

        $map = $server->getAdditionalAllocationsMap();
        $allIds = array_values(array_unique(array_merge($map['FIXED'], $map['BYUSER'])));
        $maxAdditional = isset($server->maxAdditionalAllocations) ? (int) $server->maxAdditionalAllocations : null;
        $currentUserAdds = count($map['BYUSER']);

        if ($maxAdditional !== null && $maxAdditional >= 0 && $currentUserAdds >= $maxAdditional) {
            return $response->json(['error' => 'Limite de alocações adicionais do usuário atingido.'])->status(403);
        }

        if ($allocationId <= 0) {
            $allocationId = self::pickRandomAvailableAllocation((string) $server->nodeUuid, $allIds);
            if ($allocationId <= 0) {
                return $response->json(['error' => 'Sem portas disponiveis no node.'])->status(404);
            }
        }

        if (in_array($allocationId, $allIds, true)) {
            return $response->json(['error' => 'Allocation já adicionada.'])->status(409);
        }

        $allocation = Allocation::find($allocationId);
        if (!$allocation || (string) $allocation->nodeId !== (string) $server->nodeUuid) {
            return $response->json(['error' => 'Allocation não pertence ao node do servidor.'])->status(400);
        }

        if (!empty($allocation->assignedTo)) {
            return $response->json(['error' => 'Allocation já está em uso.'])->status(409);
        }

        $allocation->assignedTo = (string) $server->id;
        $allocation->save();

        $map['BYUSER'][] = $allocationId;
        $map['BYUSER'] = array_values(array_unique($map['BYUSER']));

        $server->additionalAllocations = json_encode($map, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $server->save();

        return $response->json(self::buildAdditionalAllocationsPayload($server));
    }

    private static function pickRandomAvailableAllocation(string $nodeId, array $excludeIds = []): int
    {
        $pdo = DB::getPdo();
        $params = [$nodeId];
        $excludeClause = '';

        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $excludeClause = " AND `id` NOT IN ({$placeholders})";
            $params = array_merge($params, $excludeIds);
        }

        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $randomFn = $driver === 'sqlite' ? 'RANDOM()' : 'RAND()';

        $stmt = $pdo->prepare(
            "SELECT `id` FROM `allocations` WHERE `nodeId` = ? AND (`assignedTo` IS NULL OR `assignedTo` = ''){$excludeClause} ORDER BY {$randomFn} LIMIT 1"
        );
        $stmt->execute($params);
        $id = (int) $stmt->fetchColumn();
        return $id > 0 ? $id : 0;
    }

    public static function removeAdditionalAllocation(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof \models\Server)) {
            return $response->json([
                'error' => 'Invalid server.'
            ])->status(400);
        }

        $allocationId = (int) ($request->getBody()['allocationId'] ?? 0);
        if ($allocationId <= 0) {
            return $response->json(['error' => 'Allocation inválida.'])->status(400);
        }

        $map = $server->getAdditionalAllocationsMap();
        if (in_array($allocationId, $map['FIXED'], true)) {
            return $response->json(['error' => 'Allocation fixa não pode ser removida.'])->status(403);
        }

        if (!in_array($allocationId, $map['BYUSER'], true)) {
            return $response->json(['error' => 'Allocation não encontrada na lista.'])->status(404);
        }

        $allocation = Allocation::find($allocationId);
        if ($allocation && (string) $allocation->assignedTo === (string) $server->id) {
            $allocation->assignedTo = null;
            $allocation->save();
        }

        $map['BYUSER'] = array_values(array_diff($map['BYUSER'], [$allocationId]));
        $server->additionalAllocations = json_encode($map, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $server->save();

        return $response->json(self::buildAdditionalAllocationsPayload($server));
    }

    private static function buildAdditionalAllocationsPayload(\models\Server $server): array
    {
        $map = $server->getAdditionalAllocationsMap();
        $ids = array_values(array_unique(array_merge($map['FIXED'], $map['BYUSER'])));
        $allocationsById = self::fetchAllocationsByIds($ids);

        $additional = [];
        foreach ($map['FIXED'] as $id) {
            if (isset($allocationsById[$id])) {
                $additional[] = $allocationsById[$id] + ['type' => 'FIXED'];
            }
        }
        foreach ($map['BYUSER'] as $id) {
            if (isset($allocationsById[$id])) {
                $additional[] = $allocationsById[$id] + ['type' => 'BYUSER'];
            }
        }

        return [
            'additionalAllocations' => $additional,
            'availableAllocations' => self::fetchAvailableAllocations((string) $server->nodeUuid),
        ];
    }

    private static function fetchAllocationsByIds(array $ids): array
    {
        if (empty($ids)) return [];

        $pdo = DB::getPdo();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT `id`, `nodeId`, `ip`, `externalIp`, `port`, `assignedTo` FROM `allocations` WHERE `id` IN ({$placeholders})");
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) continue;
            $out[$id] = [
                'id' => $id,
                'nodeId' => $row['nodeId'] ?? null,
                'ip' => $row['ip'] ?? null,
                'externalIp' => $row['externalIp'] ?? null,
                'port' => (int) ($row['port'] ?? 0),
            ];
        }

        return $out;
    }

    private static function fetchAvailableAllocations(string $nodeId): array
    {
        $pdo = DB::getPdo();
        $stmt = $pdo->prepare("SELECT `id`, `nodeId`, `ip`, `externalIp`, `port` FROM `allocations` WHERE `nodeId` = :nodeId AND (`assignedTo` IS NULL OR `assignedTo` = '') ORDER BY `port` ASC LIMIT 500");
        $stmt->execute(['nodeId' => $nodeId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'nodeId' => $row['nodeId'] ?? null,
                'ip' => $row['ip'] ?? null,
                'externalIp' => $row['externalIp'] ?? null,
                'port' => (int) ($row['port'] ?? 0),
            ];
        }, $rows);
    }

}