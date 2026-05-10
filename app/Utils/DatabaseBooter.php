<?php

use Vatts\Vatts;

$type = env("DB_TYPE", "sqlite");

$dbName = env("DB_DATABASE", "panel.db");
try {
    if($type === 'sqlite') {
        Vatts::bootDatabase([
            'driver'   => "sqlite",
            'database' => "../$dbName", // Caminho completo do banco
            'charset'  => env("DB_CHARSET", "utf8mb4")
        ]);

    } elseif ($type === 'mysql') {
        Vatts::bootDatabase([
            'driver'   => "mysql",
            'host'     => env("DB_HOST", "localhost"),
            'database' => env("DB_DATABASE", "panel"),
            'username' => env("DB_USERNAME", "root"),
            'password' => env("DB_PASSWORD", ""),
            'charset'  => env("DB_CHARSET", "utf8mb4")
        ]);
    } else {
        throw new Exception("Tipo de banco de dados não suportado: $type");
    }
} catch (Exception $e) {
    error_log("Erro ao conectar ao banco de dados: " . $e->getMessage());
    die("Erro ao conectar ao banco de dados. Verifique os logs para mais detalhes.");
}