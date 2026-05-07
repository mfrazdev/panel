<?php

namespace App\controllers\Api\Servers;

use App\Services\DatabaseHostsApi;
use models\DatabaseHosts;
use models\Server;
use Vatts\Router\Request;
use Vatts\Router\Response;

class ServerDatabasesApiController
{
    /**
     * Retorna a lista de bancos de dados do servidor
     */
    public static function getDatabases(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof Server)) {
            return $response->json(['error' => 'Servidor inválido.'])->status(400);
        }

        return $response->json([
            'maxDatabases' => $server->maxDatabases,
            'databases'    => $server->getDatabasesList()
        ]);
    }

    /**
     * Cria um novo Banco de Dados e vincula ao Servidor
     */
    public static function createDatabase(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof Server)) {
            return $response->json(['error' => 'Servidor inválido.'])->status(400);
        }

        // Verifica o limite de bancos de dados
        $currentDbs = $server->getDatabasesList();
        $maxDbs = $server->maxDatabases;

        if ($maxDbs !== null && count($currentDbs) >= $maxDbs) {
            return $response->json([
                'error' => "Limite atingido. Você só pode criar até {$maxDbs} banco(s) de dados neste servidor."
            ])->status(403);
        }

        // Puxa todos os hosts disponíveis (você pode adaptar para buscar um específico se o user escolher)
        $hosts = DatabaseHosts::all();
        if (empty($hosts)) {
            return $response->json(['error' => 'Nenhum servidor de banco de dados (Host) configurado no painel administrador.'])->status(503);
        }

        // Escolhe um host aleatoriamente para balancear a criação
        $host = $hosts[array_rand($hosts)];

        // Gera os nomes e senhas
        $randomHash = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 5); // 5 caracteres aleatórios
        
        $dbName   = "db{$server->id}_{$randomHash}";
        $dbUser   = "u{$server->id}_{$randomHash}";
        $password = self::generateStrongPassword(20);

        // Comunica com o servidor MySQL remoto (usando a classe DatabaseHostsApi que fizemos antes)
        $api = new DatabaseHostsApi($host);
        
        if (!$api->createDatabaseAndUser($dbName, $dbUser, $password)) {
            return $response->json(['error' => 'Falha ao provisionar o banco de dados no servidor Host remoto. Tente novamente.'])->status(500);
        }

        // Salva os dados no JSON do servidor
        $dbInfo = [
            'dbName'   => $dbName,
            'dbUser'   => $dbUser,
            'password' => $password, // Lembre-se de avisar ao frontend para o usuário salvar essa senha!
            'hostId'   => $host->id,
            'hostIp'   => $host->ip,
            'hostPort' => $host->port,
            'createdAt'=> date('Y-m-d H:i:s')
        ];

        $server->addDatabase($dbInfo);

        return $response->json([
            'success'  => true,
            'database' => $dbInfo
        ]);
    }

    /**
     * Remove um Banco de Dados e seu Usuário
     */
    public static function deleteDatabase(Request $request, Response $response): Response
    {
        $server = $request->getParsed('server');
        if (!($server instanceof Server)) {
            return $response->json(['error' => 'Servidor inválido.'])->status(400);
        }

        $dbName = $request->getBody()['dbName'] ?? null;
        if (!$dbName) {
            return $response->json(['error' => 'Nome do banco de dados (dbName) não informado.'])->status(400);
        }

        $dbs = $server->getDatabasesList();
        
        // Acha os dados do DB que queremos deletar para pegarmos o HostId e dbUser
        $dbToDelete = null;
        foreach ($dbs as $db) {
            if ($db['dbName'] === $dbName) {
                $dbToDelete = $db;
                break;
            }
        }

        if (!$dbToDelete) {
            return $response->json(['error' => 'Banco de dados não encontrado neste servidor.'])->status(404);
        }

        // Busca o host
        $host = DatabaseHosts::get("id", $dbToDelete['hostId']);
        if (!$host) {
            // Se o host não existir mais, apenas removemos do JSON do server.
            $server->removeDatabase($dbName);
            return $response->json(['success' => true, 'message' => 'DB desvinculado (o Host original não foi encontrado).']);
        }

        // Remove do Host Remoto
        $api = new DatabaseHostsApi($host);
        if (!$api->dropDatabaseAndUser($dbToDelete['dbName'], $dbToDelete['dbUser'])) {
            return $response->json(['error' => 'Falha ao tentar deletar o banco de dados no Host remoto.'])->status(500);
        }

        // Desvincula do Server
        $server->removeDatabase($dbName);

        return $response->json([
            'success' => true,
            'message' => 'Banco de dados deletado com sucesso.'
        ]);
    }

    /**
     * Gera uma senha forte e aleatória
     */
    private static function generateStrongPassword(int $length = 24): string
    {
        // Pool misturado com letras maiúsculas, minúsculas, números e símbolos
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+~|<>?';
        $pass = '';
        $max = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $pass .= $chars[random_int(0, $max)];
        }
        
        return $pass;
    }
}