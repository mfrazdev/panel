<?php

namespace models;

use Vatts\Database\Model;
use Vatts\Database\DB;

class Settings extends Model
{
    protected static ?string $table = 'settings';


    public static array $schema = [
        'id'          => 'id',
        'key' => 'text',
        'value' => 'text',
    ];

    // Propriedades Obrigatórias
    public int $id;
    public string $key;
    public ?string $value;

}