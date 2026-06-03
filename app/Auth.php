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
                $email = $credentials['email'] ?? '';
                $user = filter_var($email, FILTER_VALIDATE_EMAIL)
                    ? User::get('email', $email)
                    : User::get('name', $email);

                // [SEGURANÇA] Prevenção de "Timing Attacks" (Descobrir usuários cadastrados).
                // Geramos um hash falso com o mesmo custo para enganar requisições onde o usuário não existe.
                $dummyHash = '$2y$10$usesomesillystringfore2uDLv11eM9/O4kL3v11eM9/O4kL3v1'; // Hash de 60 caracteres válido do bcrypt como placeholder
                $hashToVerify = $user ? $user->password : $dummyHash;
                $passwordToCheck = $credentials['password'] ?? '';

                $isPasswordValid = password_verify($passwordToCheck, $hashToVerify);

                if ($user && $isPasswordValid) {
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
                // [SEGURANÇA] O Carimbo de Segurança.
                // Criamos um hash com base na senha atual ou e-mail.
                // Isso fica salvo na sessão do PHP. Se no banco alterar, a sessão cai.
                'security_stamp' => isset($user['password']) ? md5($user['password']) : null,
            ];
        },

        'session' => function($sessionData) {
            $id = $sessionData['id'] ?? null;
            $sessionStamp = $sessionData['security_stamp'] ?? null;

            if ($id) {
                $user = User::get('id', $id);
                if ($user) {
                    // [SEGURANÇA] Valida se o usuário mudou a senha (o que muda o security stamp)
                    $currentStamp = isset($user->password) ? md5($user->password) : null;

                    if ($sessionStamp !== null && $currentStamp !== null && !hash_equals($currentStamp, $sessionStamp)) {
                        // O "carimbo" não bateu! Senha foi alterada em outro lugar.
                        // Ao retornar null, o novo VattsAuth::getSession() destrói a sessão imediatamente.
                        return null;
                    }

                    $userArray = $user->toArray();
                    unset($userArray['view_map']);
                    // [SEGURANÇA] Nunca enviar dados sensíveis em cache/sessão pro frontend
                    unset($userArray['password']);

                    return $userArray;
                }
            }
            return null;
        }
    ]
]);

return $auth;