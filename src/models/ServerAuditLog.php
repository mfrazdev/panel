<?php

namespace models;

use Vatts\Database\Model;

class ServerAuditLog extends Model
{
    protected static ?string $table = 'server_audit_logs';

    public static array $schema = [
        'id'         => 'id',

        // FK
        'server_id'  => 'int',

        // Quem fez
        'user_id'    => 'int',

        // Tipo da ação
        'action'     => 'text',

        // Metadata
        'ip'         => 'string',
        'userAgent'  => 'text',

    ];

    // autocomplete
    public int $id;

    public int $server_id;

    public ?int $user_id = null;

    public string $action;

    public ?string $ip = null;

    public ?string $userAgent = null;
}