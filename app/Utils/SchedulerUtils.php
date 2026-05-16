<?php

namespace App\Utils;

use App\Services\Logger;
use models\Server;

class SchedulerUtils
{

    public static function isTimeToRun(string $cronExpression, ?int $timestamp = null): bool
    {
        $timestamp = $timestamp ?: time();

        // Pega os dados do tempo atual: Minuto, Hora, Dia, Mês, Dia da Semana
        $currentDateParts = explode(' ', date('i G j n w', $timestamp));

        // Formata e separa o cron (remove espaços duplos por segurança)
        $cronExpression = preg_replace('/\s+/', ' ', trim($cronExpression));
        $cronParts = explode(' ', $cronExpression);

        // Um cron válido precisa ter exatamente 5 partes
        if (count($cronParts) !== 5) {
            return false;
        }

        foreach ($cronParts as $index => $part) {
            if (!self::matchCronPart($part, $currentDateParts[$index])) {
                return false; // Se uma das partes não bater, não é hora de rodar
            }
        }

        return true;
    }

    /**
     * Processa todos os schedulers de um servidor específico.
     * Tlgd, isso aqui vai rodar as ações e comandos de cada task!
     *
     * @param Server $server
     * @return void
     */
    public static function processServerSchedulers(Server $server): void
    {
        $schedulers = $server->getSchedulersList();

        if (empty($schedulers)) return;

        foreach ($schedulers as $scheduler) {
            // Ignora se estiver desativado
            if (empty($scheduler['active'])) continue;

            // Checa se bateu o tempo de executar
            if (self::isTimeToRun($scheduler['cron'])) {
                self::executeTasks($server, $scheduler['tasks']);
            }
        }
    }

    /**
     * Executa a lista de tasks vinculadas a um scheduler.
     *
     * @param Server $server
     * @param array $tasks
     * @return void
     */
    private static function executeTasks(Server $server, array $tasks): void
    {
        foreach ($tasks as $task) {
            $type = $task['type'] ?? ''; // 'action' ou 'command'
            $payload = $task['payload'] ?? ''; // ex: 'restart' ou 'say Mensagem'

            if (empty($type) || empty($payload)) continue;

            try {
                if ($type === 'action') {
                    // Ações de energia (start, stop, restart, kill)
                    $validActions = ['start', 'stop', 'restart', 'kill'];
                    if (in_array(strtolower($payload), $validActions)) {
                        $server->sendAction(strtolower($payload));
                    }
                } elseif ($type === 'command') {
                    // Enviar comando para o console
                    $server->sendAction('command', null, $payload);
                }

                // Se no futuro você quiser adicionar um "delay" entre as execuções de task
                // você pode fazer um sleep aqui, ex: se existir $task['delay'], dá um sleep.

            } catch (\Exception $e) {
                Logger::error("Erro ao executar task do Scheduler no servidor {$server->id}: " . $e->getMessage());
            }
        }
    }

    private static function matchCronPart(string $cronPart, string $currentValue): bool
    {
        // Se for * (sempre executa)
        if ($cronPart === '*') {
            return true;
        }

        // Se for exato (ex: 15)
        if ($cronPart === $currentValue) {
            return true;
        }

        // Se for divisor (ex: */5 para a cada 5 minutos)
        if (str_starts_with($cronPart, '*/')) {
            $step = (int) str_replace('*/', '', $cronPart);
            return $step > 0 && ((int)$currentValue % $step) === 0;
        }

        // Se for lista (ex: 1,15,30)
        if (str_contains($cronPart, ',')) {
            $allowedValues = explode(',', $cronPart);
            return in_array($currentValue, $allowedValues);
        }

        // Você também pode adicionar range (1-5) aqui no futuro se quiser!

        return false;
    }
}