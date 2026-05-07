<?php

namespace models;

use Vatts\Database\Model;

class DatabaseHosts extends Model
{
    protected static ?string $table = 'databasehosts';

    // Campos que não devem ser expostos em JSON (caso necessário)
    protected static array $hidden = ["password"];

    /**
     * Mapa de visualização para o painel administrativo.
     */
    public array $view_map = [
        'Geral' => [
            ['label' => 'Endereço IP', 'key' => 'ip', 'type' => 'text', 'desc' => 'Endereço IP interno para o servidor mysql.'],
            ['label' => 'Porta', 'key' => 'port', 'type' => 'number', 'desc' => 'Porta numérica para conexão.'],
            ['label' => 'Usuário', 'key' => 'username', 'type' => 'text', 'desc' => 'Usuário com acesso root para conexão.'],
            ['label' => 'Senha', 'key' => 'password', 'type' => 'password', 'desc' => 'Senha para conexão do usuário root.'],
            ['label' => 'Nome do Host', 'key' => 'name', 'type' => 'text', 'desc' => 'Um nome amigável para identificar este host de banco de dados. Ex: "MySQL do Servidor 1".']
        ]
    ];

    /**
     * Definição do Schema para o ORM.
     */
    public static array $schema = [
        'id'         => 'id',
        'name'     => 'string',
        'ip'         => 'string',
        'port'       => 'int',
        'username' => 'string',
        'password' => 'string'
    ];

    public int $id;
    public string $name;
    public string $ip;
    public int $port;
    public string $username;    
    public string $password;

}