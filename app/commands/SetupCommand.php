<?php

namespace App\commands;

use Vatts\Console\Command;
use Vatts\Console\Terminal\Console;

class SetupCommand extends Command
{
    public function getName(): string
    {
        return 'env:setup';
    }

    public function getDescription(): string
    {
        return 'Configura as informações do sistema (Nome e URL).';
    }

    public function handle(array $args): void
    {
        Console::info('--- Configuração do Sistema ---');

        $envPath = '.env';

        // Verifica se o .env existe (ou seja, se o env:db já foi executado)
        if (!file_exists($envPath)) {
            Console::error('Arquivo .env não encontrado!');
            Console::info('Por favor, configure o banco de dados primeiro executando:');
            Console::info('php cmd env:db');
            return;
        }

        // Coletar Nome e URL do painel usando a classe Console
        $appName = Console::ask("Qual o nome do Painel/Empresa?", "Hight Cloud");
        $appUrl = Console::ask("Qual a URL do Painel?", "http://localhost");

        // Salvar as alterações no .env
        $this->updateEnv([
            'APP_NAME' => '"' . $appName . '"',
            'APP_URL'  => $appUrl
        ], $envPath);

        Console::success('Configurações de sistema salvas com sucesso!');
    }

    /**
     * Atualiza ou adiciona chaves no arquivo .env
     */
    protected function updateEnv(array $data, string $envPath): void
    {
        $content = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}\s*=.*/m";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$value}", $content);
            } else {
                $content .= PHP_EOL . "{$key}={$value}";
            }
        }

        file_put_contents($envPath, trim($content) . PHP_EOL);
    }
}