<?php

use Vatts\Vatts;

$type = Vatts::getEnv("DB_TYPE", "sqlite");

$dbName = Vatts::getEnv("DB_DATABASE", "panel.db");
try {
    if($type === 'sqlite') {
        Vatts::bootDatabase([
            'driver'   => "sqlite",
            'database' => "../$dbName", // Caminho completo do banco
            'charset'  => Vatts::getEnv("DB_CHARSET", "utf8mb4")
        ]);

    } elseif ($type === 'mysql') {
        Vatts::bootDatabase([
            'driver'   => "mysql",
            'host'     => Vatts::getEnv("DB_HOST", "localhost"),
            'database' => Vatts::getEnv("DB_DATABASE", "panel"),
            'username' => Vatts::getEnv("DB_USERNAME", "root"),
            'password' => Vatts::getEnv("DB_PASSWORD", ""),
            'charset'  => Vatts::getEnv("DB_CHARSET", "utf8mb4")
        ]);
    } else {
        throw new Exception("Tipo de banco de dados não suportado: $type");
    }
} catch (Exception $e) {
    error_log("Erro ao conectar ao banco de dados: " . $e->getMessage());
    die("Erro ao conectar ao banco de dados. Verifique os logs para mais detalhes.");
}