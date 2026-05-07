<?php

namespace models;

use Vatts\Database\Model;

class Tokens extends Model
{
    protected static ?string $table = 'tokensapi';

    // Campos que não devem ser expostos em JSON (caso necessário)
    protected static array $hidden = [];

    /**
     * Mapa de visualização para o painel administrativo.
     */
    public array $view_map = [
        'Geral' => [
            ['label' => 'Nome do Token de Api', 'key' => 'name', 'type' => 'text', 'desc' => 'Um nome amigável para identificação.'],
            ['label' => 'Descrição', 'key' => 'desc', 'type' => 'textarea', 'desc' => 'Uma descrição opcional para ajudar a identificar o propósito deste token.'],
        ]
    ];

    /**
     * Definição do Schema para o ORM.
     */
    public static array $schema = [
        'id'         => 'id',
        'name'     => 'string',
        'desc' => 'string',
        'token' => 'string',
    ];

    public int $id;
    public string $name;
    public string $desc;
    public string $token;

}