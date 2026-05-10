<?php

namespace App\commands;

use Vatts\Console\Command;
use Vatts\Console\Terminal\Console;

class SetupDBCommand extends Command
{
    public function getName(): string
    {
        return 'env:db';
    }

    public function getDescription(): string
    {
        return 'Configura as credenciais de banco de dados no arquivo .env.last.';
    }

    public function handle(array $args): void
    {
        Console::info('--- Configuração de Banco de Dados ---');

        $envPath = '.env';
        $examplePath = '.env.example';

        // Cria o .env.last se ele ainda não existir, pois este comando roda primeiro
        if (!file_exists($envPath)) {
            if (file_exists($examplePath)) {
                copy($examplePath, $envPath);
                Console::info('Arquivo .env.last criado automaticamente a partir de .env.last.example.');
            } else {
                file_put_contents($envPath, '');
            }
        }

        $type = Console::selection("Tipo de Banco de Dados", [
            'mysql' => 'MySQL / MariaDB',
            'sqlite' => 'SQLite'
        ]);

        $envData = [];

        if ($type === 'sqlite') {
            // Lógica para SQLite
            $path = Console::ask("Caminho do arquivo SQLite", "database/database.sqlite");

            $envData = [
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE'   => $path,
                'DB_HOST'       => '127.0.0.1',
                'DB_PORT'       => '3306',
                'DB_USERNAME'   => 'root',
                'DB_PASSWORD'   => ''
            ];

            // Cria o arquivo/diretório SQLite caso não existam
            if (!file_exists($path)) {
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                touch($path);
                Console::success("Arquivo SQLite criado em: {$path}");
            }

        } else {
            // Lógica para MySQL
            $host = Console::ask("Host", "127.0.0.1");
            $port = Console::ask("Porta", "3306");
            $database = Console::ask("Database", "hightcloud_panel");
            $username = Console::ask("Usuário", "root");
            $password = Console::ask("Senha (deixe vazio se não tiver)", "");

            $envData = [
                'DB_CONNECTION' => 'mysql',
                'DB_HOST'       => $host,
                'DB_PORT'       => $port,
                'DB_DATABASE'   => $database,
                'DB_USERNAME'   => $username,
                'DB_PASSWORD'   => $password
            ];
        }

        // Atualiza o .env.last
        $this->updateEnv($envData, $envPath);

        Console::success('Credenciais de banco de dados salvas no .env.last com sucesso!');
    }

    /**
     * Atualiza ou adiciona chaves no arquivo .env.last
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