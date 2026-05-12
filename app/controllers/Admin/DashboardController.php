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

    public function update(Request $request, Response $response): Response
    {
        $currentVersion = $this->getCurrentVersion();
        $latestVersion = $this->getLatestGitHubVersion($currentVersion);

        if (!$latestVersion) {
            return $response->json(['success' => false, 'message' => 'Não foi possível verificar a versão mais recente. Tente novamente mais tarde.']);
        }

        $tagName = $latestVersion;
        $zipUrl = "https://github.com/" . self::GITHUB_REPO . "/releases/download/{$tagName}/panel.zip";
        $tempZipPath = sys_get_temp_dir() . '/panel_update_' . time() . '.zip';

        $ch = curl_init($zipUrl);
        $fp = fopen($tempZipPath, 'w+');

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Vatts-App');
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($httpCode !== 200) {
            @unlink($tempZipPath);
            return $response->json([
                'success' => false,
                'message' => "Falha ao baixar o arquivo (HTTP {$httpCode}). " . ($httpCode == 404 ? "O arquivo panel.zip não foi encontrado na release {$tagName}." : "Erro cURL: {$curlError}")
            ]);
        }

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath) === true) {
            $extractPath = realpath(__DIR__ . '/../../../');

            // --- INÍCIO DO DEBUG ---
            error_log("[Update Debug] Iniciando extração da versão {$tagName}");
            error_log("[Update Debug] Caminho de extração resolvido (realpath): " . ($extractPath ?: 'FALSO - CAMINHO INVÁLIDO'));

            if ($extractPath) {
                error_log("[Update Debug] O diretório existe. Permissão de escrita: " . (is_writable($extractPath) ? 'SIM' : 'NÃO'));
            } else {
                error_log("[Update Debug] __DIR__ atual é: " . __DIR__);
            }
            // --- FIM DO DEBUG ---

            // Sem o @ para o PHP poder registrar Warnings nativos no log se der BO
            $extractSuccess = $zip->extractTo($extractPath);

            if (!$extractSuccess) {
                // Captura o motivo interno do ZipArchive ter falhado
                error_log("[Update Debug] ZipArchive->extractTo() retornou false. Status do Zip: " . $zip->getStatusString());
            }

            $zip->close();
            unlink($tempZipPath);

            if ($extractSuccess) {
                $this->applyPermissions($extractPath);
                return $response->json(['success' => true, 'message' => 'Atualização aplicada com sucesso!']);
            } else {
                return $response->json(['success' => false, 'message' => 'Falha ao extrair o arquivo de atualização. Verifique o error_log do PHP para detalhes.']);
            }
        }

        @unlink($tempZipPath);
        return $response->json(['success' => false, 'message' => 'Falha ao abrir o arquivo de atualização. Tente novamente mais tarde.']);
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