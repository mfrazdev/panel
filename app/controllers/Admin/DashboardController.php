<?php

namespace App\controllers\Admin;

use Vatts\Router\Request;
use Vatts\Router\Response;

class DashboardController
{
    private const GITHUB_REPO = 'mfrazlab/panel'; // Substitua pelo seu repositório

    public function view(Request $request, Response $response): Response
    {
        $currentVersion = $this->getCurrentVersion();
        $latestVersion = $this->getLatestGitHubVersion();

        $hasUpdate = false;

        if ($latestVersion && $currentVersion) {
            $normalizedCurrent = ltrim(strtolower($currentVersion), 'v');
            $normalizedLatest = ltrim(strtolower($latestVersion), 'v');

            // Detecta canary/builds especiais
            $isCurrentCanary = str_contains($normalizedCurrent, 'canary');
            $isLatestCanary = str_contains($normalizedLatest, 'canary');

            /*
             * Regras:
             *
             * stable -> stable
             * canary -> canary
             * stable -> canary
             * canary -> stable
             *
             * Tudo comparando corretamente.
             */

            if ($isCurrentCanary || $isLatestCanary) {
                /*
                 * Usa comparação "natural" para versões com labels:
                 * Ex:
                 * 1.0.0-canary.1
                 * 1.0.0-canary.2
                 * 1.0.1
                 */
                $hasUpdate = version_compare(
                    $normalizedCurrent,
                    $normalizedLatest,
                    '<'
                );
            } else {
                // versões normais
                $hasUpdate = version_compare(
                    $normalizedCurrent,
                    $normalizedLatest,
                    '<'
                );
            }
        }

        return $response->view('Dashboard', [
            'current_version' => $currentVersion ?? 'Dev',
            'latest_version'  => $latestVersion ?? 'Desconhecida',
            'has_update'      => $hasUpdate
        ]);
    }

    /**
     * Lê a versão atual do arquivo gerado pelo GitHub Actions.
     */
    public static function getCurrentVersion(): ?string
    {
        // Caminho para o arquivo version.json na raiz do projeto
        // Ajuste o __DIR__ . '/../../../' dependendo de onde este controller está em relação à raiz
        $versionFile = __DIR__ . '/../../../version.json';

        if (file_exists($versionFile)) {
            $json = file_get_contents($versionFile);
            $data = json_decode($json, true);

            if (isset($data['version'])) {
                // Remove o 'v' da frente, caso o actions tenha salvo com 'v'
                return 'v' . ltrim($data['version'], 'v');
            }
        }

        return 'dev'; // Retorna 'dev' se o arquivo não existir (ex: rodando localmente sem build)
    }

    /**
     * Consulta a API do GitHub para pegar a última release
     */
    private function getLatestGitHubVersion(): ?string
    {
        // Idealmente, use um sistema de cache aqui para não esgotar o rate limit do GitHub
        // Exemplo simplificado usando $_SESSION:
        if (isset($_SESSION['latest_github_version']) && isset($_SESSION['latest_github_version_time'])) {
            if (time() - $_SESSION['latest_github_version_time'] < 3600) { // Cache de 1 hora
                return $_SESSION['latest_github_version'];
            }
        }

        $url = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases/latest';

        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Vatts-App',
                    'Accept: application/vnd.github.v3+json'
                ]
            ]
        ];

        $context = stream_context_create($options);
        $json = @file_get_contents($url, false, $context);
        error_log("GitHub API response: " . $json);
        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true);
        if (isset($data['tag_name'])) {
            $version = ltrim($data['tag_name'], 'v');

            // Salva na sessão para fazer cache
            $_SESSION['latest_github_version'] = $version;
            $_SESSION['latest_github_version_time'] = time();

            return $version;
        }

        return null;
    }
}