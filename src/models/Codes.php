<?php

namespace models;

use Vatts\Database\Model;

class Codes extends Model
{
    protected static ?string $table = 'recovery_codes';

    // Campos ocultos no JSON
    protected static array $hidden = [];

    /**
     * Mapa de visualização para o painel administrativo.
     */

    /**
     * Definição do Schema para o ORM.
     */
    public static array $schema = [
        'id'      => 'id',
        'userId'  => 'string',
        'email'   => 'string',
        'code'    => 'string',
    ];

    // Propriedades tipadas
    public int $id;
    public string $userId;
    public string $email;
    public string $code;
    public ?string $createdAt = null;
    public ?string $updatedAt = null;
}