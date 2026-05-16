<?php

namespace App\Auth;

use App\Services\Logger;
use Vatts\Auth\AuthProviderInterface;
use Vatts\Router\Request;
use Vatts\Router\Response;
use Exception;

class PaymenterProvider implements AuthProviderInterface
{
    public string $id;
    public string $name;
    public string $type = 'paymenter';

    private array $config;
    private array $defaultScope = [
        '*' // Paymenter geralmente usa escopo global ou '*' para acesso. Ajuste conforme a doc se necessário.
    ];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->id = $this->resolveConfigValue('id') ?? 'paymenter';
        $this->name = $this->resolveConfigValue('name') ?? 'Paymenter';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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

        $isPopup = filter_var($credentials['popup'] ?? false, FILTER_VALIDATE_BOOLEAN);
        return $this->getAuthorizationUrl($isPopup);
    }

    private function storeOAuthState(bool $isPopup): string
    {
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
        $key = 'oauth_state_' . $this->id;
        $stored = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        if (!is_array($stored) || $state === null) {
            return null;
        }

        if (time() > ($stored['expiresAt'] ?? 0)) {
            Logger::error("[Paymenter Provider] OAuth State expired.");
            return null;
        }

        if (!hash_equals((string)($stored['value'] ?? ''), (string)$state)) {
            Logger::error("[Paymenter Provider] OAuth State mismatch.");
            return null;
        }

        return $stored;
    }

    private function processOAuthCallback(array $credentials): ?array
    {
        try {
            $code = $credentials['code'];

            $paymenterUrl = rtrim((string) $this->resolveConfigValue('paymenterUrl'), '/');
            $clientId = (string) $this->resolveConfigValue('clientId');
            $clientSecret = (string) $this->resolveConfigValue('clientSecret');
            $callbackUrl = (string) $this->resolveConfigValue('callbackUrl');

            if (empty($paymenterUrl) || empty($clientId) || empty($clientSecret)) {
                throw new Exception("Missing Paymenter credentials or URL.");
            }

            // 1. Trocar o código pelo Token (Paymenter Laravel Passport padrão)
            $ch = curl_init($paymenterUrl . '/oauth/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Vatts.js Auth Client/1.0');

            // Header explicitando que esperamos JSON (Laravel requer isso)
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json'
            ]);

            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $callbackUrl,
                'code' => $code,
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

            // 2. Buscar os dados do usuário (Endpoint padrão da API do Paymenter)
            $chInfo = curl_init($paymenterUrl . '/api/users/@me'); // Rota típica do Paymenter/Pterodactyl
            curl_setopt($chInfo, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chInfo, CURLOPT_HTTPGET, true); // Geralmente é GET
            curl_setopt($chInfo, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($chInfo, CURLOPT_TIMEOUT, 20);
            curl_setopt($chInfo, CURLOPT_FOLLOWLOCATION, false);

            curl_setopt($chInfo, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($chInfo, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($chInfo, CURLOPT_USERAGENT, 'Vatts.js Auth Client/1.0');

            // Autenticação via Bearer Token (Padrão OAuth2/Passport)
            curl_setopt($chInfo, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $tokens['access_token'],
                'Accept: application/json'
            ]);

            $userResult = curl_exec($chInfo);
            if ($userResult === false) {
                $curlError = curl_error($chInfo);
                throw new Exception("Failed to fetch user data from Paymenter: {$curlError}");
            }
            if (curl_getinfo($chInfo, CURLINFO_HTTP_CODE) !== 200) {
                throw new Exception("Failed to fetch user data from Paymenter. HTTP " . curl_getinfo($chInfo, CURLINFO_HTTP_CODE) . " - " . $userResult);
            }

            $paymenterUser = json_decode($userResult, true);

            // Paymenter geralmente envelopa os dados em "data"
            $userData = $paymenterUser['data'] ?? $paymenterUser;

            if (!is_array($userData) || empty($userData['email'])) {
                throw new Exception("Failed to parse Paymenter user data or missing email. Result: " . $userResult);
            }

            $user = [
                'id' => $userData['id'] ?? '',
                'name' => trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? '')),
                'email' => $userData['email'] ?? '',
                'image' => null,
                'provider' => $this->id,
                'providerId' => $userData['id'] ?? '',
                'accessToken' => $tokens['access_token'],
                'refreshToken' => $tokens['refresh_token'] ?? null
            ];

            // Fallback caso o nome venha vazio (Paymenter pode usar só 'name' em algumas versões)
            if (empty(trim($user['name'])) && !empty($userData['name'])) {
                $user['name'] = $userData['name'];
            }

            $whenCallback = $this->config['whenCallback'] ?? null;
            if ($whenCallback && is_callable($whenCallback)) {
                $user = call_user_func($whenCallback, $user, $userData);

                if ($user === false) {
                    throw new Exception("Authentication denied by whenCallback.");
                }
            }

            return $user;

        } catch (Exception $error) {
            Logger::error("[{$this->id} Provider] Error during OAuth callback: " . $error->getMessage());
            throw $error;
        }
    }

    public function getAuthorizationUrl(bool $isPopup = false): string
    {
        $paymenterUrl = rtrim((string) $this->resolveConfigValue('paymenterUrl'), '/');
        $clientId = (string) $this->resolveConfigValue('clientId');
        $callbackUrl = (string) $this->resolveConfigValue('callbackUrl');
        $scope = $this->resolveConfigValue('scope', $this->defaultScope);

        $state = $this->storeOAuthState($isPopup);

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $callbackUrl,
            'response_type' => 'code',
            'scope' => implode(' ', is_array($scope) ? $scope : $this->defaultScope),
            'state' => $state,
            'prompt' => 'consent' // Garante que a tela de autorização apareça
        ];

        return $paymenterUrl . '/oauth/authorize?' . http_build_query($params);
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
                            return $res->redirect("/api/auth/popup-callback?success=false&error=Invalid+or+expired+state&provider={$this->id}");
                        }
                        return $res->status(400)->json(['error' => 'Invalid or expired OAuth state']);
                    }
                    $error = $query['error'] ?? null;
                    if ($error !== null) {
                        Logger::error("[Paymenter OAuth] Error from auth server: " . $query['error']);
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

                    try {
                        $user = $this->processOAuthCallback(['code' => $code]);

                        if ($user) {
                            session_regenerate_id(true);

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