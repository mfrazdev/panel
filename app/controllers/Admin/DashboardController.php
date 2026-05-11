<?php

namespace App\controllers\Admin;

use Vatts\Router\Request;
use Vatts\Router\Response;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DashboardController
{
    private const GITHUB_REPO = 'mfrazlab/panel'; // Substitua pelo seu repositório

    public function view(Request $request, Response $response): Response
    {
        $currentVersion = $this->getCurrentVersion();

        // Pega as informações completas da release (para saber a tag e ter os assets dps)
        $releaseInfo = $this->getLatestGitHubReleaseInfo($currentVersion);
        $latestVersion = $releaseInfo['version'] ?? null;

        $hasUpdate = false;

        if ($latestVersion && $currentVersion) {
            $normalizedCurrent = ltrim(strtolower($currentVersion), 'v');
            $normalizedLatest = ltrim(strtolower($latestVersion), 'v');

            // O PHP nativamente não sabe que "canary" é uma versão anterior a "stable".
            // Para o version_compare() funcionar com perfeição e sem bugar as versões normais:
            // Trocamos 'canary' por 'beta' apenas para a matemática interna da função.
            $cmpCurrent = str_replace('canary', 'beta', $normalizedCurrent);
            $cmpLatest  = str_replace('canary', 'beta', $normalizedLatest);

            $hasUpdate = version_compare($cmpCurrent, $cmpLatest, '<');
        }

        return $response->view('Dashboard', [
            'current_version' => $currentVersion ?? 'Dev',
            'latest_version'  => $latestVersion ?? 'Desconhecida',
            'has_update'      => $hasUpdate
        ]);
    }

    /**
     * Função para atualizar o painel automaticamente
     */
    public function update(Request $request, Response $response)
    {
        $currentVersion = $this->getCurrentVersion();
        $releaseInfo = $this->getLatestGitHubReleaseInfo($currentVersion);

        if (!$releaseInfo || empty($releaseInfo['assets'])) {
            die(json_encode(['success' => false, 'message' => 'Nenhuma release ou asset encontrado.']));
        }

        // Localiza o panel.zip nos assets
        $zipUrl = null;
        foreach ($releaseInfo['assets'] as $asset) {
            if ($asset['name'] === 'panel.zip') {
                $zipUrl = $asset['browser_download_url'];
                break;
            }
        }

        if (!$zipUrl) {
            die(json_encode(['success' => false, 'message' => 'O arquivo panel.zip não foi encontrado na última release.']));
        }

        $tempZipPath = sys_get_temp_dir() . '/panel_update_' . time() . '.zip';

        // Baixa o panel.zip usando opções para suportar redirects do GitHub
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Vatts-App',
                ]
            ]
        ];
        $zipData = @file_get_contents($zipUrl, false, stream_context_create($options));

        if ($zipData === false) {
            die(json_encode(['success' => false, 'message' => 'Falha ao baixar o arquivo de atualização do GitHub.']));
        }

        file_put_contents($tempZipPath, $zipData);

        // Descompacta
        $zip = new ZipArchive();
        if ($zip->open($tempZipPath) === true) {
            // Ajuste o caminho da extração conforme a raiz do seu projeto
            $extractPath = realpath(__DIR__ . '/../../../');

            $zip->extractTo($extractPath);
            $zip->close();

            // Apaga o zip temporário
            unlink($tempZipPath);

            // Aplica as permissões recursivamente (Linux e Windows)
            $this->applyPermissions($extractPath);

            die(json_encode(['success' => true, 'message' => 'Painel atualizado com sucesso!']));
        }

        die(json_encode(['success' => false, 'message' => 'Falha ao extrair o arquivo ZIP.']));
    }

    /**
     * Lê a versão atual do arquivo gerado pelo GitHub Actions.
     */
    public static function getCurrentVersion(): ?string
    {
        $versionFile = __DIR__ . '/../../../version.json';

        if (file_exists($versionFile)) {
            $json = file_get_contents($versionFile);
            $data = json_decode($json, true);

            if (isset($data['version'])) {
                return 'v' . ltrim($data['version'], 'v');
            }
        }

        return 'dev';
    }

    /**
     * Consulta a API do GitHub para pegar a última release (suportando Canary e Stable)
     */
    private function getLatestGitHubReleaseInfo(?string $currentVersion): ?array
    {
        if (isset($_SESSION['github_release_info']) && isset($_SESSION['github_release_time'])) {
            if (time() - $_SESSION['github_release_time'] < 3600) {
                return $_SESSION['github_release_info'];
            }
        }

        // Trocado de /releases/latest para /releases para obtermos as pré-releases (canary) também
        $url = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases';

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

        if ($json === false) {
            return null;
        }

        $releases = json_decode($json, true);
        if (!is_array($releases) || empty($releases)) {
            return null;
        }

        $isCurrentCanary = $currentVersion ? str_contains(strtolower($currentVersion), 'canary') : false;
        $selectedRelease = null;

        foreach ($releases as $release) {
            $isPreRelease = $release['prerelease'];

            // Se a nossa versão atual não for canary, pulamos qualquer pre-release do GitHub
            // garantindo que quem tá no modo estável não receba builds de canary
            if (!$isCurrentCanary && $isPreRelease) {
                continue;
            }

            // A API retorna a lista em ordem cronológica reversa, então o primeiro que passar na regra é o mais atual.
            $selectedRelease = $release;
            break;
        }

        if ($selectedRelease && isset($selectedRelease['tag_name'])) {
            $info = [
                'version' => ltrim($selectedRelease['tag_name'], 'v'),
                'assets'  => $selectedRelease['assets'] ?? []
            ];

            $_SESSION['github_release_info'] = $info;
            $_SESSION['github_release_time'] = time();

            return $info;
        }

        return null;
    }

    /**
     * Aplica as permissões nos arquivos extraídos.
     * Funciona no Linux nativamente. No Windows, ele apenas mapeia os atributos
     * suportados pelo SO (read-only) sem estourar nenhum erro.
     */
    private function applyPermissions(string $dir): void
    {
        if (!is_dir($dir)) return;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if ($item->isDir()) {
                @chmod($path, 0755); // R/W/X para o owner, R/X para os demais
            } else {
                @chmod($path, 0644); // R/W para o owner, R para os demais
            }
        }
    }
}