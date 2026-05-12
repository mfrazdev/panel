<?php

namespace App\controllers\Api\Users\Servers;

require_once __DIR__ . '/../../../../../vendor/autoload.php';

use models\Server;
use Vatts\Router\Request;
use Vatts\Router\Response;
use Rakit\Validation\Validator;

class ServerSchedulersApiController
{
    /**
     * Retorna a lista de todos os agendamentos do servidor
     */
    public static function list(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof Server)) {
            return $response->json([
                'error' => 'Servidor inválido.'
            ])->status(400);
        }

        return $response->json([
            'success' => true,
            'schedulers' => $server->getSchedulersList()
        ]);
    }

    /**
     * Cria um novo agendamento (Task)
     */
    public static function create(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof Server)) {
            return $response->json([
                'error' => 'Servidor inválido.'
            ])->status(400);
        }

        $body = $request->getBody();

        // Validação básica dos dados recebidos
        $validator = new Validator([
            'required' => 'O campo :attribute é obrigatório.',
            'array'    => 'O campo :attribute deve ser um array.',
            'boolean'  => 'O campo :attribute deve ser verdadeiro ou falso.'
        ]);

        $validation = $validator->make($body, [
            'name'     => 'required',
            'cron'     => 'required', // ex: "*/5 * * * *" ou "0 12 * * *"
            'tasks'    => 'required|array',
            'isActive' => 'boolean'
        ]);

        $validation->validate();

        if ($validation->fails()) {
            return $response->json([
                'error' => $validation->errors()->firstOfAll()
            ])->status(400);
        }

        // Valida se o cron tem 5 partes usando explode por espaços
        $cronParts = explode(' ', preg_replace('/\s+/', ' ', trim($body['cron'])));
        if (count($cronParts) !== 5) {
            return $response->json([
                'error' => 'A expressão Cron inválida. Ela deve conter exatamente 5 parâmetros separados por espaço (ex: * * * * *).'
            ])->status(400);
        }

        // Valida se as tasks enviadas possuem os formatos corretos
        $tasks = $body['tasks'];
        if (empty($tasks)) {
            return $response->json([
                'error' => 'Você precisa adicionar ao menos uma ação (task) neste agendamento.'
            ])->status(400);
        }

        foreach ($tasks as $task) {
            if (empty($task['type']) || empty($task['payload'])) {
                return $response->json([
                    'error' => 'Cada task deve ter um "type" (action ou command) e um "payload" (comando ou ação).'
                ])->status(400);
            }
            if (!in_array($task['type'], ['action', 'command'])) {
                return $response->json([
                    'error' => 'O tipo de task deve ser "action" ou "command".'
                ])->status(400);
            }
        }

        $isActive = isset($body['isActive']) ? (bool)$body['isActive'] : true;

        $schedulerId = $server->addScheduler($body['name'], $body['cron'], $tasks, $isActive);

        return $response->json([
            'success' => true,
            'message' => 'Agendamento criado com sucesso!',
            'schedulerId' => $schedulerId
        ]);
    }

    /**
     * Remove um agendamento existente
     */
    public static function delete(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof Server)) {
            return $response->json([
                'error' => 'Servidor inválido.'
            ])->status(400);
        }

        $body = $request->getBody();
        $schedulerId = $body['schedulerId'] ?? null;

        if (!$schedulerId) {
            return $response->json([
                'error' => 'O ID do agendamento é obrigatório.'
            ])->status(400);
        }

        $server->removeScheduler($schedulerId);

        return $response->json([
            'success' => true,
            'message' => 'Agendamento removido com sucesso.'
        ]);
    }

    /**
     * Ativa ou desativa um agendamento existente
     */
    public static function toggle(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof Server)) {
            return $response->json([
                'error' => 'Servidor inválido.'
            ])->status(400);
        }

        $body = $request->getBody();
        $schedulerId = $body['schedulerId'] ?? null;
        $isActive = $body['isActive'] ?? null;

        if (!$schedulerId || $isActive === null) {
            return $response->json([
                'error' => 'O ID do agendamento e o status (isActive) são obrigatórios.'
            ])->status(400);
        }

        $server->toggleScheduler($schedulerId, (bool)$isActive);

        return $response->json([
            'success' => true,
            'message' => 'Status do agendamento atualizado com sucesso.'
        ]);
    }

    /**
     * Edita um agendamento existente
     */
    public static function edit(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof Server)) {
            return $response->json([
                'error' => 'Servidor inválido.'
            ])->status(400);
        }

        $body = $request->getBody();

        // Validação básica
        $validator = new Validator([
            'required' => 'O campo :attribute é obrigatório.',
            'array'    => 'O campo :attribute deve ser um array.',
            'boolean'  => 'O campo :attribute deve ser verdadeiro ou falso.'
        ]);

        $validation = $validator->make($body, [
            'schedulerId' => 'required',
            'name'        => 'required',
            'cron'        => 'required',
            'tasks'       => 'required|array',
            'isActive'    => 'boolean'
        ]);

        $validation->validate();

        if ($validation->fails()) {
            return $response->json([
                'error' => $validation->errors()->firstOfAll()
            ])->status(400);
        }

        // Valida se o cron tem 5 partes
        $cronParts = explode(' ', preg_replace('/\s+/', ' ', trim($body['cron'])));
        if (count($cronParts) !== 5) {
            return $response->json([
                'error' => 'A expressão Cron inválida. Ela deve conter exatamente 5 parâmetros separados por espaço (ex: * * * * *).'
            ])->status(400);
        }

        // Valida as tasks enviadas
        $tasks = $body['tasks'];
        if (empty($tasks)) {
            return $response->json([
                'error' => 'Você precisa adicionar ao menos uma ação (task) neste agendamento.'
            ])->status(400);
        }

        foreach ($tasks as $task) {
            if (empty($task['type']) || empty($task['payload'])) {
                return $response->json([
                    'error' => 'Cada task deve ter um "type" (action ou command) e um "payload" (comando ou ação).'
                ])->status(400);
            }
            if (!in_array($task['type'], ['action', 'command'])) {
                return $response->json([
                    'error' => 'O tipo de task deve ser "action" ou "command".'
                ])->status(400);
            }
        }

        $isActive = isset($body['isActive']) ? (bool)$body['isActive'] : true;

        // Atualiza no banco
        $updated = $server->editScheduler($body['schedulerId'], $body['name'], $body['cron'], $tasks, $isActive);

        if (!$updated) {
            return $response->json([
                'error' => 'Agendamento não encontrado.'
            ])->status(404);
        }

        return $response->json([
            'success' => true,
            'message' => 'Agendamento atualizado com sucesso!'
        ]);
    }
}