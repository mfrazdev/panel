<?php

namespace App\Auth;

use App\Services\Logger;
use Exception;
use Vatts\Auth\AuthProviderInterface;
use Vatts\Router\Request;
use Vatts\Router\Response;

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
        $this->id = $this->resolveConfigValue('id') ?? 'whmcs';
        $this->name = $this->resolveConfigValue('name') ?? 'WHMCS';
    }

    public function getId(): string
    {
        return $this->id;
    }

    private function resolveConfigValue(string $key, $default = null)
    {
        if (!isset($this->config[$key])) {
            return $default;
        }

        $value = $this->config[$key];

        if (is_callable($value)) {
            return call_user_func($value);
        }

        return $value;
    }

    public function handleSignIn(array $credentials, Request $req)
    {
        if (!empty($credentials['code'])) {
            return $this->processOAuthCallback($credentials);
        }

        // Usa o filter_var para interpretar corretamente true, "true" ou "1"
        $isPopup = filter_var($credentials['popup'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Se não tem código, retorna a URL de autorização (Front-end vai redirecionar)
        return $this->getAuthorizationUrl($isPopup);
    }

    private function storeOAuthState(bool $isPopup): string
    {
        // Garante a sessão apenas na hora de usar, onde as configs já foram carregadas
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $state = bin2hex(random_bytes(32));
        $expiresAt = time() + 600;

        $_SESSION['oauth_state_' . $this->id] = [
            'value' => $state,
            'popup' => $isPopup,
            'expiresAt' => $expiresAt,
        ];

        return $state;
    }

    private function consumeOAuthState(?string $state): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $key = 'oauth_state_' . $this->id;
        $stored = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        if (!is_array($stored) || $state === null) {
            return null;
        }

        if (time() > ($stored['expiresAt'] ?? 0)) {
            Logger::error("[WHMCS Provider] OAuth State expired.");
            return null;
        }

        if (!hash_equals((string)($stored['value'] ?? ''), (string)$state)) {
            Logger::error("[WHMCS Provider] OAuth State mismatch.");
            return null;
        }

        return $stored;
    }

    /**
     * Aplica os mesmos parâmetros de expiração do cookie de sessão utilizados globalmente
     */
    protected function forceCookieExpiration(): void
    {
        $sessionConfig = $this->config['session'] ?? [];
        $lifetimeDays = (int) ($sessionConfig['lifetime_days'] ?? 30);
        $lifetime = max(0, $lifetimeDays * 86400);

        $secure = $sessionConfig['secure'] ?? (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $httpOnly = $sessionConfig['httponly'] ?? true;
        $sameSite = $sessionConfig['samesite'] ?? 'Lax';
        $path = $sessionConfig['path'] ?? '/';
        $domain = $sessionConfig['domain'] ?? '';

        if (PHP_VERSION_ID >= 70300) {
            setcookie(session_name(), session_id(), [
                'expires' => time() + $lifetime,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httpOnly,
                'samesite' => $sameSite,
            ]);
        } else {
            setcookie(session_name(), session_id(), time() + $lifetime, $path, $domain, $secure, $httpOnly);
        }
    }

    private function processOAuthCallback(array $credentials): ?array
    {
        try {
            // [SEGURANÇA] Prevenção de Array Injection
            if (empty($credentials['code']) || !is_string($credentials['code'])) {
                throw new Exception("Invalid code format provided.");
            }
            $code = $credentials['code'];

            $whmcsUrl = rtrim((string) $this->resolveConfigValue('whmcsUrl'), '/');
            $clientId = (string) $this->resolveConfigValue('clientId');
            $clientSecret = (string) $this->resolveConfigValue('clientSecret');
            $callbackUrl = (string) $this->resolveConfigValue('callbackUrl');

            if (empty($whmcsUrl) || empty($clientId) || empty($clientSecret)) {
                throw new Exception("Missing WHMCS credentials or URL.");
            }

            $ch = curl_init($whmcsUrl . '/oauth/token.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

            // [SEGURANÇA CRÍTICA] Bypass de SSL removido para impedir ataques Man-In-The-Middle
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            // Finge ser um navegador para o firewall do WHMCS não chiar
            curl_setopt($ch, CURLOPT_USERAGENT, 'Vatts.js Auth Client/1.0');

            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $callbackUrl,
            ]));

            $tokenResult = curl_exec($ch);
            if ($tokenResult === false) {
                $curlError = curl_error($ch);
                throw new Exception("Failed to exchange code for token: {$curlError}");
            }
            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
                throw new Exception("Failed to exchange code for token: HTTP " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . " - " . $tokenResult);
            }

            $tokens = json_decode($tokenResult, true);
            if (!is_array($tokens) || empty($tokens['access_token'])) {
                throw new Exception("Failed to parse access token response. Result: " . $tokenResult);
            }

            $chInfo = curl_init($whmcsUrl . '/oauth/userinfo.php');
            curl_setopt($chInfo, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chInfo, CURLOPT_POST, true);
            curl_setopt($chInfo, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($chInfo, CURLOPT_TIMEOUT, 20);
            curl_setopt($chInfo, CURLOPT_FOLLOWLOCATION, false);

            // [SEGURANÇA CRÍTICA] Bypass de SSL removido
            curl_setopt($chInfo, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($chInfo, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($chInfo, CURLOPT_USERAGENT, 'Vatts.js Auth Client/1.0');

            // Mantemos no header para garantir...
            curl_setopt($chInfo, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $tokens['access_token'],
                'Accept: application/json'
            ]);

            // O PULO DO GATO: Mandar o token no body também!
            curl_setopt($chInfo, CURLOPT_POSTFIELDS, http_build_query([
                'access_token' => $tokens['access_token']
            ]));

            $userResult = curl_exec($chInfo);
            if ($userResult === false) {
                $curlError = curl_error($chInfo);
                throw new Exception("Failed to fetch user data from WHMCS: {$curlError}");
            }
            if (curl_getinfo($chInfo, CURLINFO_HTTP_CODE) !== 200) {
                throw new Exception("Failed to fetch user data from WHMCS. HTTP " . curl_getinfo($chInfo, CURLINFO_HTTP_CODE) . " - " . $userResult);
            }

            $whmcsUser = json_decode($userResult, true);
            if (!is_array($whmcsUser)) {
                throw new Exception("Failed to parse WHMCS user data. Result: " . $userResult);
            }

            if (empty($whmcsUser['email'])) {
                throw new Exception("WHMCS did not return an email address.");
            }

            $user = [
                'id' => $whmcsUser['sub'] ?? $whmcsUser['id'] ?? '',
                'name' => $whmcsUser['name'] ?? '',
                'email' => $whmcsUser['email'] ?? '',
                'image' => null,
                'provider' => $this->id,
                'providerId' => $whmcsUser['sub'] ?? $whmcsUser['id'] ?? '',
                'accessToken' => $tokens['access_token'],
                'refreshToken' => $tokens['refresh_token'] ?? null
            ];

            $whenCallback = $this->config['whenCallback'] ?? null;
            if ($whenCallback && is_callable($whenCallback)) {
                $user = call_user_func($whenCallback, $user, $whmcsUser);

                if ($user === false) {
                    throw new Exception("Authentication denied by whenCallback.");
                }
            }

            return $user;

        } catch (Exception $error) {
            Logger::error("[{$this->id} Provider] Error during OAuth callback: " . $error->getMessage());
            // Agora estamos propagando o erro para exibir no JSON final
            throw $error;
        }
    }

    public function getAuthorizationUrl(bool $isPopup = false): string
    {
        $whmcsUrl = rtrim((string) $this->resolveConfigValue('whmcsUrl'), '/');
        $clientId = (string) $this->resolveConfigValue('clientId');
        $callbackUrl = (string) $this->resolveConfigValue('callbackUrl');
        $scope = $this->resolveConfigValue('scope', $this->defaultScope);

        $state = $this->storeOAuthState($isPopup);

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $callbackUrl,
            'response_type' => 'code',
            'scope' => implode(' ', is_array($scope) ? $scope : $this->defaultScope),
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

                    // [SEGURANÇA] Força a variável state a ser null se for injetado um array
                    $stateParam = isset($query['state']) && is_string($query['state']) ? $query['state'] : null;

                    $stateData = $this->consumeOAuthState($stateParam);
                    $isPopup = (bool)($stateData['popup'] ?? false);

                    if (!$stateData) {
                        if ($isPopup) {
                            return $res->redirect("/api/auth/popup-callback?success=false&error=Invalid+or+expired+state&provider={$this->id}");
                        }
                        return $res->status(400)->json(['error' => 'Invalid or expired OAuth state']);
                    }
                    $error = $query['error'] ?? null;
                    if ($error !== null) {
                        Logger::error("[WHMCS OAuth] Error from auth server: " . $query['error']);
                        if ($isPopup) {
                            return $res->redirect("/api/auth/popup-callback?success=false&error=Authorization+cancelled&provider={$this->id}");
                        }
                        return $res->status(400)->json(['error' => 'Authorization cancelled or failed']);
                    }

                    if (!$code) {
                        if ($isPopup) {
                            return $res->redirect("/api/auth/popup-callback?success=false&error=Code+missing&provider={$this->id}");
                        }
                        return $res->status(400)->json(['error' => 'Authorization code not provided']);
                    }

                    // Processa o callback capturando o erro real agora
                    try {
                        $user = $this->processOAuthCallback(['code' => $code]);

                        if ($user) {
                            if (session_status() === PHP_SESSION_NONE) {
                                session_start();
                            }

                            // [SEGURANÇA] Correção de Session Fixation
                            session_regenerate_id(true);
                            $this->forceCookieExpiration();

                            $_SESSION['vatts_auth_user'] = $user;
                            $_SESSION['vatts_auth_ua'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                            $_SESSION['vatts_auth_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                            $_SESSION['vatts_auth_last_activity'] = time();

                            if ($isPopup) {
                                $successUrl = $this->resolveConfigValue('successUrl', '/');
                                return $res->redirect("/api/auth/popup-callback?success=true&provider={$this->id}&callbackUrl=" . urlencode((string)$successUrl));
                            }

                            $successUrl = $this->resolveConfigValue('successUrl');
                            if (!empty($successUrl)) {
                                return $res->redirect((string)$successUrl);
                            }

                            return $res->json(['success' => true]);
                        }

                    } catch (Exception $e) {
                        if ($isPopup) {
                            return $res->redirect("/api/auth/popup-callback?success=false&error=" . urlencode($e->getMessage()) . "&provider={$this->id}");
                        }
                        return $res->status(500)->json(['error' => 'Authentication failed: ' . $e->getMessage()]);
                    }
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
            'callbackUrl' => is_string($this->config['callbackUrl'] ?? null) ? $this->config['callbackUrl'] : null,
        ];
    }
}