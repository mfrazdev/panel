<?php

namespace App;

use Vatts\Auth\AuthProviderInterface;
use Vatts\Router\Request;
use Vatts\Router\Response;
use Exception;

class WHMCSProvider implements AuthProviderInterface
{
    public string $id;
    public string $name;
    public string $type = 'whmcs';

    private array $config;
    private array $defaultScope = [
        'openid',
        'profile',
        'email'
    ];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->id = $config['id'] ?? 'whmcs';
        $this->name = $config['name'] ?? 'WHMCS';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function handleSignIn(array $credentials, Request $req)
    {
        if (!empty($credentials['code'])) {
            return $this->processOAuthCallback($credentials);
        }

        // Se não tem código, retorna a URL de autorização (Front-end vai redirecionar)
        return $this->getAuthorizationUrl($credentials['popup'] === 'true');
    }

    private function storeOAuthState(bool $isPopup): string
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state_' . $this->id] = [
            'value' => $state,
            'popup' => $isPopup,
            'createdAt' => time(),
        ];
        return $state;
    }

    private function consumeOAuthState(?string $state): ?array
    {
        $key = 'oauth_state_' . $this->id;
        $stored = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        if (!is_array($stored) || $state === null) {
            return null;
        }

        if (!hash_equals((string)($stored['value'] ?? ''), (string)$state)) {
            return null;
        }

        return $stored;
    }

    private function processOAuthCallback(array $credentials): ?array
    {
        try {
            $code = $credentials['code'];
            $whmcsUrl = rtrim($this->config['whmcsUrl'], '/');

            // Troca o código por um access token via cURL
            $ch = curl_init($whmcsUrl . '/oauth/token.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'client_id' => $this->config['clientId'],
                'client_secret' => $this->config['clientSecret'],
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->config['callbackUrl'] ?? '',
            ]));

            $tokenResult = curl_exec($ch);
            if ($tokenResult === false) {
                $curlError = curl_error($ch);
                curl_close($ch);
                throw new Exception("Failed to exchange code for token: {$curlError}");
            }
            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
                throw new Exception("Failed to exchange code for token: " . $tokenResult);
            }
            curl_close($ch);

            $tokens = json_decode($tokenResult, true);
            if (!is_array($tokens) || empty($tokens['access_token'])) {
                throw new Exception("Failed to parse access token response.");
            }

            // Busca dados do usuário via cURL
            // Nota: O controller original usava POST para o userinfo, mantendo o padrão.
            $chInfo = curl_init($whmcsUrl . '/oauth/userinfo.php');
            curl_setopt($chInfo, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chInfo, CURLOPT_POST, true);
            curl_setopt($chInfo, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($chInfo, CURLOPT_TIMEOUT, 20);
            curl_setopt($chInfo, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($chInfo, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($chInfo, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $tokens['access_token']
            ]);

            $userResult = curl_exec($chInfo);
            if ($userResult === false) {
                $curlError = curl_error($chInfo);
                curl_close($chInfo);
                throw new Exception("Failed to fetch user data from WHMCS: {$curlError}");
            }
            if (curl_getinfo($chInfo, CURLINFO_HTTP_CODE) !== 200) {
                throw new Exception("Failed to fetch user data from WHMCS");
            }
            curl_close($chInfo);

            $whmcsUser = json_decode($userResult, true);
            if (!is_array($whmcsUser)) {
                throw new Exception("Failed to parse WHMCS user data.");
            }

            if (empty($whmcsUser['email'])) {
                throw new Exception("WHMCS did not return an email address.");
            }

            $user = [
                // O WHMCS geralmente retorna 'sub' como identificador no OpenID, fazendo fallback para id.
                'id' => $whmcsUser['sub'] ?? $whmcsUser['id'] ?? '',
                'name' => $whmcsUser['name'] ?? '',
                'email' => $whmcsUser['email'] ?? '',
                'image' => null, // WHMCS tipicamente não provê imagem padrão via userinfo
                'provider' => $this->id,
                'providerId' => $whmcsUser['sub'] ?? $whmcsUser['id'] ?? '',
                'accessToken' => $tokens['access_token'],
                'refreshToken' => $tokens['refresh_token'] ?? null
            ];

            // Executa o callback customizado, se existir
            if (isset($this->config['whenCallback']) && is_callable($this->config['whenCallback'])) {
                $user = call_user_func($this->config['whenCallback'], $user, $whmcsUser);

                // Se retornar false, barra o login
                if ($user === false) {
                    throw new Exception("Authentication denied by whenCallback.");
                }
            }

            return $user;

        } catch (Exception $error) {
            error_log("[{$this->id} Provider] Error during OAuth callback: " . $error->getMessage());
            return null;
        }
    }

    public function getAuthorizationUrl(bool $isPopup = false): string
    {
        $whmcsUrl = rtrim($this->config['whmcsUrl'], '/');

        $state = $this->storeOAuthState($isPopup);

        $params = [
            'client_id' => $this->config['clientId'],
            'redirect_uri' => $this->config['callbackUrl'] ?? '',
            'response_type' => 'code',
            'scope' => implode(' ', $this->config['scope'] ?? $this->defaultScope),
            'state' => $state
        ];

        return $whmcsUrl . '/oauth/authorize.php?' . http_build_query($params);
    }

    public function getAdditionalRoutes(): array
    {
        return [
            [
                'method' => 'GET',
                'path' => "/api/auth/callback/{$this->id}",
                'handler' => function (Request $req, Response $res) {
                    $query = $req->getQuery();
                    $code = $query['code'] ?? null;
                    $stateData = $this->consumeOAuthState($query['state'] ?? null);
                    $isPopup = (bool)($stateData['popup'] ?? false);

                    if (!$stateData) {
                        if ($isPopup) {
                            return $res->redirect("/api/auth/popup-callback?success=false&error=Invalid+state&provider={$this->id}");
                        }
                        return $res->status(400)->json(['error' => 'Invalid OAuth state']);
                    }

                    if ($req->hasQuery('error')) {
                        error_log("[WHMCS OAuth] Erro recebido na URL de callback: " . $query['error']);
                        if ($isPopup) {
                            return $res->redirect("/api/auth/popup-callback?success=false&error=Authorization+cancelled+or+failed&provider={$this->id}");
                        }
                        return $res->status(400)->json(['error' => 'Authorization cancelled or failed']);
                    }

                    if (!$code) {
                        if ($isPopup) {
                            return $res->redirect("/api/auth/popup-callback?success=false&error=Authorization+code+not+provided&provider={$this->id}");
                        }
                        return $res->status(400)->json(['error' => 'Authorization code not provided']);
                    }

                    // Processa o callback diretamente
                    $user = $this->processOAuthCallback(['code' => $code]);

                    if ($user) {
                        // Seta a sessão nativamente no PHP (Simulando o POST interno do JS)
                        $_SESSION['vatts_auth_user'] = $user;

                        if ($isPopup) {
                            $callbackUrl = $this->config['successUrl'] ?? '/';
                            return $res->redirect("/api/auth/popup-callback?success=true&provider={$this->id}&callbackUrl=" . urlencode($callbackUrl));
                        }

                        if (!empty($this->config['successUrl'])) {
                            return $res->redirect($this->config['successUrl']);
                        }
                        return $res->json(['success' => true]);
                    }

                    // Erro
                    if ($isPopup) {
                        return $res->redirect("/api/auth/popup-callback?success=false&error=Session+creation+failed&provider={$this->id}");
                    }
                    return $res->status(500)->json(['error' => 'Session creation failed']);
                }
            ]
        ];
    }

    public function getConfig(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'whmcsUrl' => $this->config['whmcsUrl'] ?? null,
            'clientId' => $this->config['clientId'],
            'scope' => $this->config['scope'] ?? $this->defaultScope,
            'callbackUrl' => $this->config['callbackUrl'] ?? null
        ];
    }
}