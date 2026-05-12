<?php

namespace App\commands;

use App\Utils\SchedulerUtils;
use Vatts\Console\Command;
use Vatts\Console\Terminal\Console;
use models\Server;
use Vatts\Vatts;

class RunSchedulersCommand extends Command
{
    public function getName(): string
    {
        return 'schedulers:run';
    }

    public function getDescription(): string
    {
        return 'Processa e executa os agendamentos (Cron) de todos os servidores ativos.';
    }

    public function handle(array $args): void
    {
        Vatts::loadEnv(__DIR__ . '/../../');
        require_once __DIR__ . '/../Utils/DatabaseBooter.php';
        // Marca o tempo de início para medir a performance
        $startTime = microtime(true);
        Console::info('[' . date('Y-m-d H:i:s') . '] Iniciando verificação de Schedulers...');

        try {
            // Pega apenas servidores que não estão suspensos
            // Dependendo da sua ORM, se for um array associativo use: ['suspended' => 0]
            $servers = Server::all();

            if (empty($servers)) {
                Console::info('Nenhum servidor encontrado para processar.');
                return;
            }

            $countProcessed = 0;
            $countErrors = 0;

            foreach ($servers as $server) {
                try {
                    // O método processServerSchedulers já checa se é a hora certa (isTimeToRun)
                    SchedulerUtils::processServerSchedulers($server);
                    $countProcessed++;
                } catch (\Throwable $e) {
                    // Se der erro neste servidor, a gente loga, mas NÃO trava o foreach!
                    $countErrors++;
                    Console::error("[Servidor #{$server->id}] Falha ao processar schedulers: " . $e->getMessage());
                }
            }

            // Marca o tempo de fim e calcula o total
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime), 4);

            if ($countErrors > 0) {
                Console::success("Schedulers verificados em {$countProcessed} servidores. Tivemos {$countErrors} erro(s). Tempo total: {$executionTime}s");
            } else {
                Console::success("Schedulers processados perfeitamente em {$countProcessed} servidores. Tempo total: {$executionTime}s");
            }

        } catch (\Throwable $e) {
            // Isso só acontece se o banco de dados explodir ou algo muito grave
            Console::error("Erro fatal ao listar servidores para os schedulers: " . $e->getMessage());
        }
    }
}