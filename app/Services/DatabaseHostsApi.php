<?php

namespace App\Services;

use models\DatabaseHosts;
use PDO;
use PDOException;

class DatabaseHostsApi
{
    private DatabaseHosts $host;

    public function __construct(DatabaseHosts $host)
    {
        $this->host = $host;
    }

    /**
     * Retorna a conexão PDO com o host de banco de dados
     */
    private function getConnection(): PDO
    {
        $dsn = "mysql:host={$this->host->ip};port={$this->host->port};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5 // Timeout de 5s para não travar a aplicação se o host estiver offline
        ];
        
        return new PDO($dsn, $this->host->username, $this->host->password, $options);
    }

    /**
     * Testa se a conexão com o host está funcionando
     */
    public function testConnection(): bool
    {
        try {
            $this->getConnection();
            return true;
        } catch (PDOException $e) {
            // Em ambiente de desenvolvimento você pode logar o $e->getMessage() aqui
            return false;
        }
    }

    /**
     * Cria um novo Banco de Dados e um Usuário com todos os privilégios nele
     */
    public function createDatabaseAndUser(string $dbName, string $dbUser, string $dbPassword): bool
    {
        // Sanitização básica para evitar SQL Injection em DDL (onde não podemos usar binds do PDO)
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);
        $dbUser = preg_replace('/[^a-zA-Z0-9_]/', '', $dbUser);

        try {
            $pdo = $this->getConnection();
            
            // O PDO::quote escapa a string da senha com segurança
            $escapedPassword = $pdo->quote($dbPassword);

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
            $pdo->exec("CREATE USER '$dbUser'@'%' IDENTIFIED BY $escapedPassword");
            $pdo->exec("GRANT ALL PRIVILEGES ON `$dbName`.* TO '$dbUser'@'%'");
            $pdo->exec("FLUSH PRIVILEGES");
            
            return true;
        } catch (PDOException $e) {
            Logger::error($e);
            return false;
        }
    }

    /**
     * Deleta o Banco de Dados e o Usuário respectivo
     */
    public function dropDatabaseAndUser(string $dbName, string $dbUser): bool
    {
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);
        $dbUser = preg_replace('/[^a-zA-Z0-9_]/', '', $dbUser);

        try {
            $pdo = $this->getConnection();
            
            $pdo->exec("DROP DATABASE IF EXISTS `$dbName`");
            $pdo->exec("DROP USER IF EXISTS '$dbUser'@'%'");
            $pdo->exec("FLUSH PRIVILEGES");
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}