<?php

namespace App\controllers\Api\Server;

use App\Services\Logger;
use models\ServerAuditLog;
use mysql_xdevapi\Exception;
use Vatts\Database\DB;
use Vatts\Router\Request;
use Vatts\Router\Response;

class ServerController
{
    public static function saveNameAndDesc(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof \models\Server)) {
            return $response->json(['error' => 'Servidor inválido.'])->status(400);
        }

        $body = $request->getBody();
        $name = trim($body["name"] ?? '');
        $desc = trim($body["desc"] ?? '');
        $group = trim($body["group"] ?? '');
        $group = $group === '' ? null : $group;

        // Validação do Nome
        if ($name === '') return $response->json(['error' => 'O nome é obrigatório.'])->status(400);
        if (mb_strlen($name) > 20) return $response->json(['error' => 'O nome deve ter no máximo 20 caracteres.'])->status(400);
        if (!preg_match('/^[a-zA-Z0-9 |\-]+$/', $name)) {
            return $response->json(['error' => 'O nome só pode conter letras, números, espaços, "|" e "-".'])->status(400);
        }

        // Validação da Descrição
        if (mb_strlen($desc) > 200) {
            return $response->json(['error' => 'A descrição deve ter no máximo 200 caracteres.'])->status(400);
        }
        if ($desc !== '' && preg_match('/^\s+$/', $desc)) {
            return $response->json(['error' => 'A descrição não pode conter apenas espaços.'])->status(400);
        }

        // Validação do Group
        if ($group !== null) {
            if (mb_strlen($group) > 12) return $response->json(['error' => 'O grupo deve ter no máximo 12 caracteres.'])->status(400);
            if (!preg_match('/^[A-Za-zÀ-ÿ\s]+$/', $group)) return $response->json(['error' => 'O grupo só pode conter letras e espaços.'])->status(400);
            if (preg_match('/^\s+$/', $group)) return $response->json(['error' => 'O grupo não pode conter apenas espaços.'])->status(400);
        }

        $server->name = $name;
        $server->description = $desc;
        $server->group = $group ?? '';
        $server->save();


        try {
            $audit = new ServerAuditLog();
            $audit->server_id = $server->id;
            $audit->user_id = $request->getParsed('user')->id;
            $audit->action = 'Atualização de Nome/Descrição/Grupo';
            $audit->ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $audit->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $audit->save();
        } catch (\Exception $exception) {
            Logger::error("Failed to log server audit: " . $exception->getMessage());
        }

        return $response->json(["success" => true]);
    }

    public static function getServer(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof \models\Server)) {
            return $response->json(['error' => 'Invalid server.'])->status(400);
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
            return $response->json(['error' => 'Invalid server.'])->status(400);
        }

        $action = $request->getBody()['action'] ?? null;
        if (!in_array($action, ['start', 'restart', 'stop', 'kill', 'command', 'install'])) {
            return $response->json([
                'error' => 'Invalid action. Allowed actions are: start, restart, stop, kill, command, install.'
            ])->status(400);
        }

        $result = $server->sendAction($action, $request->getParsed('user')->id);
        if ($result === false) {
            return $response->json(['error' => 'Failed to send action. Node might be offline or unreachable.'])->status(503);
        }

        return $response->json(['success' => true, 'result' => $result]);
    }

    public static function getStatus(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof \models\Server)) {
            return $response->json(['error' => 'Invalid server.'])->status(400);
        }

        $status = $server->getStatus();
        if ($status === false) {
            return $response->json(['error' => 'Node is offline or unreachable.'])->status(503);
        }

        return $response->json(['status' => $status]);
    }

    public static function getAudit(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof \models\Server)) {
            return $response->json(['error' => 'Invalid server.'])->status(400);
        }
        $audits = ServerAuditLog::witch('server_id', $server->id)->orderBy('created_at', 'desc')->get();
        foreach ($audits as $audit) {
            $pdo = DB::getPdo();
            $sql = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = :id");
            $sql->execute(['id' => $audit->user_id]);
            $user = $sql->fetch();
            if ($user) {
                $audit->user = [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name']
                ];
            } else $audit->user = [];
        }
        return $response->json(['audits' => $audits]);
    }

}