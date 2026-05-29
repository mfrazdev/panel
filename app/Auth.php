<?php

use models\Settings;
use models\User;
use Vatts\Auth\Providers\CredentialsProvider;
use Vatts\Auth\VattsAuth;
use Vatts\Vatts;

if (!function_exists('getConfigs')) {
    function getConfigs(): array
    {
        $settings = Settings::all();
        $mapped = [];

        foreach ($settings as $model) {
            $mapped[$model->key] = $model->value;
        }
        return $mapped;
    }
}

$auth = new VattsAuth([
    'session' => [
        'lifetime_days' => 30,
        'idle_timeout' => 2592000,
        'bind_ip' => false,
        'samesite' => 'Lax',
        'secure' => str_contains(Vatts::getEnv('URL'), 'https'),
    ],

    'providers' => [
        new CredentialsProvider([
            'authorize' => function ($credentials) {
                $email = $credentials['email'];
                $user = filter_var($email, FILTER_VALIDATE_EMAIL)
                    ? User::get('email', $email)
                    : User::get('name', $email);

                $pass = $credentials['password'] ?? null;
                \App\Services\Logger::info("Attempting login for user: {$email} and {$pass}");

                if ($user && password_verify($credentials['password'], $user->password)) {
                    $u = $user->toArray();
                    unset($u['view_map']);
                    return $u;
                }
                return null;
            }
        ]),

        new \App\Auth\WHMCSProvider([
            'id' => 'whmcs',
            'clientId' => function () {
                return getConfigs()['oauth_client_id'] ?? null;
            },
            'clientSecret' => function () {
                return getConfigs()['oauth_client_secret'] ?? null;
            },
            'whmcsUrl' => function () {
                return getConfigs()['oauth_url'] ?? null;
            },
            'callbackUrl' => Vatts::getEnv('URL') . '/api/auth/callback/whmcs',
            'whenCallback' => function($user1, $whmcsUser) {
                $email = $whmcsUser['email'] ?? null;
                if (!$email) {
                    return false;
                }
                $user = User::get('email', $email);
                if (!$user) {
                    return false;
                }

                $u = $user->toArray();
                unset($u['view_map']);
                return $u;
            }
        ]),

        new \App\Auth\PaymenterProvider([
            'id' => 'paymenter',
            'clientId' => function () {
                return getConfigs()['oauth_client_id'] ?? null;
            },
            'clientSecret' => function () {
                return getConfigs()['oauth_client_secret'] ?? null;
            },
            'paymenterUrl' => function () {
                return getConfigs()['oauth_url'] ?? null;
            },
            'callbackUrl' => Vatts::getEnv('URL') . '/api/auth/callback/paymenter',
            'whenCallback' => function($user1, $paymenterUser) {
                $email = $paymenterUser['email'] ?? null;
                if (!$email) {
                    return false;
                }
                $user = User::get('email', $email);
                if (!$user) {
                    return false;
                }

                $u = $user->toArray();
                unset($u['view_map']);
                return $u;
            }
        ])
    ],

    'callbacks' => [
        'jwt' => function($user) {
            return [
                'id' => $user['id'],
            ];
        },

        'session' => function($sessionData) {
            $id = $sessionData['id'] ?? null;
            if ($id) {
                $user = User::get('id', $id);
                if ($user) {
                    $user =  $user->toArray();
                    unset($user['view_map']);
                    return $user;
                }
            }
            return $sessionData;
        }
    ]
]);

return $auth;
