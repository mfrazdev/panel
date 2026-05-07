<?php

namespace App\controllers\Api\admin;

use Vatts\Router\Request;
use Vatts\Router\Response;
use App\Services\ServerService;
use models\Server;

class ServersController
{
    private ServerService $serverService;

    public function __construct()
    {
        $this->serverService = new ServerService();
    }

    public function create(Request $request, Response $response): Response
    {
        $body = $request->getBody();

        try {
            // Delega toda a lógica pesada de criação para o Service
            $server = $this->serverService->createServer($body);

            return $response->json([
                'success' => true,
                'message' => 'Servidor criado com sucesso e requisitado ao node.',
                'server'  => [
                    'id'         => $server->id,
                    'serverUuid' => $server->serverUuid,
                    'name'       => $server->name,
                    'nodeId'     => $server->nodeUuid,
                    'allocation' => $server->allocationId
                ]
            ])->status(201); // 201 Created

        } catch (\Exception $e) {
            error_log($e);
            return $response->json([
                'success' => false,
                'error'   => $e->getMessage()
            ])->status(400); // 400 Bad Request
        }
    }

    public function edit(Request $request, Response $response): Response
    {
        // Pega o ID da rota (ex: /api/admin/servers/[id]) ou do corpo da requisição
        $serverId = $request->getParam('id') ?? ($request->getBody()['id'] ?? null);

        if (!$serverId) {
            return $response->json([
                'success' => false,
                'error'   => 'ID do servidor não fornecido.'
            ])->status(400);
        }

        $server = Server::find($serverId);

        if (!$server) {
            return $response->json([
                'success' => false,
                'error'   => 'Servidor não encontrado.'
            ])->status(404);
        }

        $body = $request->getBody();
        $hasUpdates = false;

        // Atualização de RAM
        if (array_key_exists('ram', $body)) {
            $server->ram = max(0, (int)$body['ram']);
            $hasUpdates = true;
        }

        // Atualização de CPU
        if (array_key_exists('cpu', $body)) {
            $server->cpu = max(0, (int)$body['cpu']);
            $hasUpdates = true;
        }

        // Atualização de Disco
        if (array_key_exists('disk', $body)) {
            $server->disk = max(0, (int)$body['disk']);
            $hasUpdates = true;
        }

        // Atualização de Suspensão
        if (array_key_exists('suspended', $body)) {
            $server->suspended = (int)$body['suspended'];
            $hasUpdates = true;
        }

        // Atualização de Máximo de Bancos de Dados
        if (array_key_exists('maxDatabases', $body)) {
            $rawMaxDb = trim((string) $body['maxDatabases']);
            $server->maxDatabases = $rawMaxDb === '' ? null : max(0, (int) $rawMaxDb);
            $hasUpdates = true;
        }

        // Atualização de Máximo de Alocações Adicionais
        if (array_key_exists('maxAdditionalAllocations', $body)) {
            $rawMax = trim((string) $body['maxAdditionalAllocations']);
            $server->maxAdditionalAllocations = $rawMax === '' ? null : max(0, (int) $rawMax);
            $hasUpdates = true;
        }

        if (!$hasUpdates) {
            return $response->json([
                'success' => false,
                'error'   => 'Nenhum dado válido para atualização foi fornecido. Envie pelo menos um campo (ram, cpu, disk, suspended, maxDatabases, maxAdditionalAllocations).'
            ])->status(400);
        }

        try {
            $server->save();

            unset($server->view_map);

            return $response->json([
                'success' => true,
                'message' => 'Servidor atualizado com sucesso.',
                'data'    => $server
            ])->status(200);

        } catch (\Exception $e) {
            error_log($e);
            return $response->json([
                'success' => false,
                'error'   => 'Erro ao atualizar servidor: ' . $e->getMessage()
            ])->status(500);
        }
    }

    public function delete(Request $request, Response $response): Response
    {
        // Pega o ID da rota (ex: /api/admin/servers/[id]) ou do corpo da requisição
        $serverId = $request->getParam('id') ?? ($request->getBody()['id'] ?? null);

        if (!$serverId) {
            return $response->json([
                'success' => false,
                'error'   => 'ID do servidor não fornecido.'
            ])->status(400);
        }

        $server = Server::find($serverId);

        if (!$server) {
            return $response->json([
                'success' => false,
                'error'   => 'Servidor não encontrado.'
            ])->status(404); // 404 Not Found
        }

        $mode = $request->getQuery()['mode'] ?? 'safe';
        $user = $request->getParsed('user');
        $userUuid = $user->id ?? $server->ownerId;

        try {
            // Delega a comunicação com o Node e limpeza do banco para o Service
            $this->serverService->deleteServer($server, (string) $userUuid, $mode);

            return $response->json([
                'success' => true,
                'message' => 'Servidor excluído com sucesso.'
            ])->status(200);

        } catch (\Exception $e) {
            error_log($e);
            return $response->json([
                'success' => false,
                'error'   => $e->getMessage()
            ])->status(500); // 500 Internal Server Error
        }
    }

    public function list(Request $request, Response $response): Response
    {
        try {
            $servers = Server::all();

            // Formatando o output para garantir que seja um array limpo para a API
            $output = array_map(function($s) {
                unset($s->view_map);
                return $s;
            }, (array) $servers);

            return $response->json([
                'success' => true,
                'data'    => $output
            ])->status(200);

        } catch (\Exception $e) {
            error_log($e);
            return $response->json([
                'success' => false,
                'error'   => 'Erro ao listar servidores: ' . $e->getMessage()
            ])->status(500);
        }
    }
}