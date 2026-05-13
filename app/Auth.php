<?php

use models\User;
use Vatts\Auth\Providers\CredentialsProvider;
use Vatts\Auth\VattsAuth;
use Vatts\Vatts;

// Configuramos a instância
$whmcsClientId = (string) Vatts::getEnv('WHMCS_CLIENT_ID', '');
$whmcsClientSecret = (string) Vatts::getEnv('WHMCS_CLIENT_SECRET', '');
$whmcsUrl = rtrim((string) Vatts::getEnv('WHMCS_URL', ''), '/');
$whmcsCallbackUrl = (string) Vatts::getEnv('WHMCS_CALLBACK_URL', '');
$whmcsSuccessUrl = (string) Vatts::getEnv('WHMCS_SUCCESS_URL', '');

$auth = new VattsAuth([
    'providers' => [
        new CredentialsProvider([
            'authorize' => function ($credentials) {
                $email = $credentials['email'];
                $user = filter_var($email, FILTER_VALIDATE_EMAIL)
                    ? User::get('email', $email)
                    : User::get('name', $email);

                if ($user && password_verify($credentials['password'], $user->password)) {
                    return $user->toArray();
                }
                return null;
            }
        ]),
        new \App\WHMCSProvider([
            'id' => 'whmcs',
            'clientId' => $whmcsClientId,
            'clientSecret' => $whmcsClientSecret,
            'whmcsUrl' => $whmcsUrl,
            'callbackUrl' => $whmcsCallbackUrl,
            'successUrl' => $whmcsSuccessUrl,
            'whenCallback' => function($user1, $whmcsUser) {
                $email = $whmcsUser['email'] ?? null;
                if (!$email) {
                    return false;
                }
                $user = User::get('email', $email);
                if (!$user) {
                    return false;
                }


                // Retorna os dados modificados para salvar na sessão
                return $user->toArray();
            }
        ])
    ],
    'callbacks' => [
        // Altera o que vai ser SALVO na sessão ($_SESSION)
        'jwt' => function($user) {
            return [
                'id' => $user['id'],
            ];
        },
        // Altera o que o FRONT-END recebe quando bate no GET /api/auth/session
        'session' => function($sessionData) {
            $id = $sessionData['id'] ?? null;
            if ($id) {
                $user = User::get('id', $id);
                if ($user) {
                    return User::get('id', $id)->toArray();
                }
            }
            return $sessionData;
        }
    ]
]);

// Retornamos a instância para que possa ser capturada por outro arquivo
return $auth;