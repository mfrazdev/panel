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
        // Força a limpeza de cache do GitHub caso passe ?check=1 na URL
        if ($request->getQuery('check') == '1') {
            unset($_SESSION['github_release_info']);
            unset($_SESSION['github_release_time']);
        }

        $currentVersion = $this->getCurrentVersion();
        $releaseInfo = $this->getLatestGitHubReleaseInfo($currentVersion);
        $latestVersion = $releaseInfo['version'] ?? null;

        $hasUpdate = false;

        if ($latestVersion && $currentVersion && strtolower($currentVersion) !== 'dev') {
            $normalizedCurrent = ltrim(strtolower($currentVersion), 'v');
            $normalizedLatest = ltrim(strtolower($latestVersion), 'v');

            // Nativamente, o PHP converte hífen em ponto e compara as partes.
            // Para garantir 100% de precisão (ex: 0.0.1-canary.7 vs 0.0.1-canary.8),
            // transformamos a string 'canary' temporariamente na keyword 'rc'
            $cmpCurrent = str_replace('canary', 'rc', $normalizedCurrent);
            $cmpLatest  = str_replace('canary', 'rc', $normalizedLatest);

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
        header('Content-Type: application/json'); // Garante que a saída seja sempre JSON

        $currentVersion = $this->getCurrentVersion();

        unset($_SESSION['github_release_info']);
        unset($_SESSION['github_release_time']);

        $releaseInfo = $this->getLatestGitHubReleaseInfo($currentVersion);

        if (!$releaseInfo || empty($releaseInfo['assets'])) {
            die(json_encode(['success' => false, 'message' => 'Nenhuma release ou asset encontrado.']));
        }

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

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath) === true) {
            $extractPath = realpath(__DIR__ . '/../../../');

            // Segura os Warnings do PHP para não sujarem nosso JSON
            ob_start();
            $extractSuccess = @$zip->extractTo($extractPath); // @ oculta o Warning
            ob_get_clean(); // Limpa qualquer resíduo de erro da memória

            $zip->close();
            unlink($tempZipPath);

            if ($extractSuccess) {
                $this->applyPermissions($extractPath);
                unset($_SESSION['github_release_info']);
                unset($_SESSION['github_release_time']);
                die(json_encode(['success' => true, 'message' => 'Painel atualizado com sucesso!']));
            } else {
                // Se a extração falhou (geralmente por permissão), avisa o usuário com um JSON limpo
                die(json_encode([
                    'success' => false,
                    'message' => 'Permissão negada ao extrair a atualização. Rode "chown -R www-data:www-data /var/www/lunar" no terminal da sua máquina e tente novamente.'
                ]));
            }
        }

        die(json_encode(['success' => false, 'message' => 'Falha ao abrir o arquivo ZIP baixado.']));
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
            if (time() - $_SESSION['github_release_time'] < 60) {
                return $_SESSION['github_release_info'];
            }
        }

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

            if (!$isCurrentCanary && $isPreRelease) {
                continue;
            }

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