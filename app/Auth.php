<?php

use models\User;
use Vatts\Auth\Providers\CredentialsProvider;
use Vatts\Auth\VattsAuth;

// Configuramos a instância
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
            'clientId' => 'HIGHT-CLOUD.47c67f876b759297b16c3e6544d6ba26',
            'clientSecret' => '+Cy7MRf56wPXu3W3f0LDCsC522RqV0vomQ0fhu9FEjlqGPHosuOqa3Oap+fn8sZNAEw355snysOjvqXv8vYtJw==',
            'whmcsUrl' => 'https://hightcloud.app', // Obrigatório agora!
            'callbackUrl' => 'https://localhost:8000/api/auth/callback/whmcs',
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