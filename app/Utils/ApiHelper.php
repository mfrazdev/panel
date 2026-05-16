<?php

namespace App\Utils;

use Vatts\Router\Response;

class ApiHelper
{
    /**
     * Gera uma string aleatória
     */
    public static function generateRandomString(int $qntd): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $string = '';

        for ($i = 0; $i < $qntd; $i++) {
            $string .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $string;
    }

    /**
     * Força o envio dos headers e do corpo para o cliente,
     * encerrando a conexão HTTP, mas mantendo o script rodando em background.
     */
    public static function emitAndDisconnect(Response $response): void
    {
        ignore_user_abort(true);
        set_time_limit(0);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $body = (string) $response->getBody();

        $response->header('Connection', 'close');
        $response->header('Content-Length', (string) strlen($body));

        http_response_code($response->getStatus());

        foreach ($response->getHeaders() as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    header(sprintf('%s: %s', $key, $v), false);
                }
            } else {
                header(sprintf('%s: %s', $key, $value));
            }
        }

        echo $body;

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            ob_flush();
            flush();
        }

        if (session_id()) {
            session_write_close();
        }
    }
}