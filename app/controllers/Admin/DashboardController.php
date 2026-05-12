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
        $latestVersion = $this->getLatestGitHubVersion($currentVersion);

        $hasUpdate = false;

        if ($latestVersion && $currentVersion && strtolower($currentVersion) !== 'dev') {
            $normalizedCurrent = ltrim(strtolower($currentVersion), 'v');
            $normalizedLatest = ltrim(strtolower($latestVersion), 'v');

            // Substitui 'canary' por 'rc' para o PHP comparar corretamente (rc = Release Candidate)
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

    public function update(Request $request, Response $response)
    {
        header('Content-Type: application/json');

        $currentVersion = $this->getCurrentVersion();
        $latestVersion = $this->getLatestGitHubVersion($currentVersion);

        if (!$latestVersion) {
            die(json_encode(['success' => false, 'message' => 'Não foi possível encontrar a última versão no GitHub.']));
        }

        // Formata a tag de volta para vX.X.X caso não tenha
        $tagName = str_starts_with($latestVersion, 'v') ? $latestVersion : 'v' . $latestVersion;

        // URL direta e estática de download (Não passa por API)
        $zipUrl = "https://github.com/" . self::GITHUB_REPO . "/releases/download/{$tagName}/panel.zip";

        $tempZipPath = sys_get_temp_dir() . '/panel_update_' . time() . '.zip';

        // Baixa o arquivo ZIP seguindo redirecionamentos da CDN do GitHub
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Vatts-App\r\n",
                'follow_location' => 1,
                'max_redirects' => 5
            ]
        ];

        $zipData = @file_get_contents($zipUrl, false, stream_context_create($options));

        if ($zipData === false) {
            die(json_encode(['success' => false, 'message' => 'Falha ao baixar o arquivo de atualização.']));
        }

        file_put_contents($tempZipPath, $zipData);

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath) === true) {
            $extractPath = realpath(__DIR__ . '/../../../');

            ob_start();
            $extractSuccess = @$zip->extractTo($extractPath);
            ob_get_clean();

            $zip->close();
            unlink($tempZipPath);

            if ($extractSuccess) {
                $this->applyPermissions($extractPath);
                die(json_encode(['success' => true, 'message' => 'Painel atualizado com sucesso!']));
            } else {
                die(json_encode([
                    'success' => false,
                    'message' => 'Permissão negada ao extrair a atualização. Rode "chown -R www-data:www-data /var/www" e tente novamente.'
                ]));
            }
        }

        die(json_encode(['success' => false, 'message' => 'Falha ao abrir o arquivo ZIP baixado.']));
    }

    public static function getCurrentVersion(): ?string
    {
        $versionFile = __DIR__ . '/../../../version.json';

        if (file_exists($versionFile)) {
            $json = @file_get_contents($versionFile);
            $data = json_decode($json, true);

            if (isset($data['version'])) {
                return 'v' . ltrim($data['version'], 'v');
            }
        }

        return 'dev';
    }

    /**
     * MAGIA NEGRA: Busca a versão pelo FEED ATOM do GitHub em vez da API REST.
     * É instantâneo, real-time, não tem rate-limit chato e lê pre-releases (canary).
     */
    private function getLatestGitHubVersion(?string $currentVersion): ?string
    {
        $url = 'https://github.com/' . self::GITHUB_REPO . '/releases.atom';

        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Vatts-App\r\n"
            ]
        ];

        // Baixa o XML incrivelmente leve e rápido
        $xmlData = @file_get_contents($url, false, stream_context_create($options));

        if ($xmlData === false) {
            return null;
        }

        $xml = @simplexml_load_string($xmlData);
        if (!$xml || !isset($xml->entry)) {
            return null;
        }

        $isCurrentCanary = $currentVersion ? str_contains(strtolower($currentVersion), 'canary') : false;

        // Varre as últimas releases do arquivo XML
        foreach ($xml->entry as $entry) {
            // O link href sempre contém a tag da release, ex: /releases/tag/v1.0.5-canary.2
            $link = (string) $entry->link['href'];

            if (preg_match('/\/releases\/tag\/(.+)$/', $link, $matches)) {
                $tag = $matches[1];
                $isPreRelease = str_contains(strtolower($tag), 'canary');

                // Se eu NÃO sou canary, ignoro tudo que tiver canary no nome
                if (!$isCurrentCanary && $isPreRelease) {
                    continue;
                }

                // Retorna a primeira versão válida que encontrar (a mais recente)
                return ltrim($tag, 'v');
            }
        }

        return null;
    }

    private function applyPermissions(string $dir): void
    {
        if (!is_dir($dir)) return;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if ($item->isDir()) {
                @chmod($path, 0755);
            } else {
                @chmod($path, 0644);
            }
        }
    }
}