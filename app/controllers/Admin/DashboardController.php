<?php

namespace App\controllers\Admin;

use App\Services\Logger;
use Vatts\Router\Request;
use Vatts\Router\Response;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DashboardController
{
    private const GITHUB_REPO = 'mfrazdev/panel'; // Substitua pelo seu repositório

    public function view(Request $request, Response $response): Response
    {

        $currentData = $this->getCurrentVersionData();
        $latestData = $this->getLatestGitHubVersionData($currentData['version']);

        $hasUpdate = false;

        $currentVersion = $currentData['version'];
        $latestVersion = $latestData['version'];

        if ($latestVersion && $currentVersion && strtolower($currentVersion) !== 'dev') {
            $normalizedCurrent = ltrim(strtolower($currentVersion), 'v');
            $normalizedLatest = ltrim(strtolower($latestVersion), 'v');

            // Se for exatamente a mesma versão e for canary, desempata pelo tempo (Timestamp)
            if ($normalizedCurrent === $normalizedLatest && str_contains($normalizedCurrent, 'canary')) {
                // Dá 5 minutos (300s) de tolerância pois o tempo do release no GitHub
                // sempre será alguns minutos APÓS o build_time gravado dentro do arquivo
                if ($latestData['release_time'] > ($currentData['build_time'] + 300)) {
                    $hasUpdate = true;
                }
            } else {
                // Substitui 'canary' por 'rc' para o PHP comparar corretamente (rc = Release Candidate)
                $cmpCurrent = str_replace('canary', 'rc', $normalizedCurrent);
                $cmpLatest  = str_replace('canary', 'rc', $normalizedLatest);
                $hasUpdate = version_compare($cmpCurrent, $cmpLatest, '<');
            }
        }

        return $response->view('Dashboard', [
            'current_version' => $currentVersion === 'dev' ? 'Dev' : $currentVersion,
            'latest_version'  => $latestVersion ?? 'Desconhecida',
            'has_update'      => $hasUpdate,
            'user'          => $request->getParsed('user'), // Usuário logado
        ]);
    }

    public function update(Request $request, Response $response): Response
    {
        $currentData = $this->getCurrentVersionData();
        $latestData = $this->getLatestGitHubVersionData($currentData['version']);

        if (!$latestData['version']) {
            return $response->json(['success' => false, 'message' => 'Não foi possível verificar a versão mais recente. Tente novamente mais tarde.']);
        }

        $tagName = $latestData['version'];
        $zipUrl = "https://github.com/" . self::GITHUB_REPO . "/releases/download/{$tagName}/panel.zip";

        // [SEGURANÇA] Prevenção de Symlink Attack (Predictable Temp File)
        // Usar tempnam garante a criação de um arquivo único seguro a nível de SO, impedindo a sobreposição de arquivos maliciosos locais.
        $tempZipPath = tempnam(sys_get_temp_dir(), 'vatts_update_');

        if ($tempZipPath === false) {
            return $response->json(['success' => false, 'message' => 'Falha interna ao criar diretório temporário para a atualização.']);
        }

        $ch = curl_init($zipUrl);
        $fp = fopen($tempZipPath, 'w+');

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Vatts-App');
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        // [SEGURANÇA] Impede ataques Man-in-the-Middle forçando a validação do certificado SSL do GitHub
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($httpCode !== 200) {
            @unlink($tempZipPath);
            return $response->json([
                'success' => false,
                'message' => "Falha ao baixar o arquivo (HTTP {$httpCode}). " . ($httpCode == 404 ? "O arquivo panel.zip não foi encontrado na release {$tagName}." : "Erro de Conexão. Verifique os logs.")
            ]);
        }

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath) === true) {
            $extractPath = realpath(__DIR__ . '/../../../');

            Logger::info("[Update Debug] Iniciando extração da versão {$tagName}");
            Logger::info("[Update Debug] Caminho de extração resolvido (realpath): " . ($extractPath ?: 'FALSO - CAMINHO INVÁLIDO'));

            $extractSuccess = $zip->extractTo($extractPath);

            if (!$extractSuccess) {
                Logger::error("[Update Debug] ZipArchive->extractTo() retornou false. Status do Zip: " . $zip->getStatusString());
            }

            $zip->close();
            unlink($tempZipPath);

            if ($extractSuccess) {
                $this->applyPermissions($extractPath);
                return $response->json(['success' => true, 'message' => 'Atualização aplicada com sucesso!']);
            } else {
                return $response->json(['success' => false, 'message' => 'Falha ao extrair o arquivo de atualização.']);
            }
        }

        @unlink($tempZipPath);
        return $response->json(['success' => false, 'message' => 'Falha ao abrir o arquivo de atualização. Tente novamente mais tarde.']);
    }

    public static function getCurrentVersion(): ?string
    {
        $versionData = self::getCurrentVersionData();
        return $versionData['version'] ?? null;
    }

    public static function getCurrentVersionData(): array
    {
        $versionFile = __DIR__ . '/../../../version.json';
        $default = ['version' => 'dev', 'build_time' => 0];

        if (file_exists($versionFile)) {
            $json = @file_get_contents($versionFile);
            $data = json_decode($json, true);

            if (isset($data['version'])) {
                return [
                    'version'    => 'v' . ltrim($data['version'], 'v'),
                    'build_time' => $data['build_time'] ?? 0 // Lê o timestamp que a Action salvou
                ];
            }
        }

        return $default;
    }

    /**
     * Extrai a última versão E O HORÁRIO pelo FEED ATOM do GitHub.
     */
    private function getLatestGitHubVersionData(?string $currentVersion): array
    {
        $default = ['version' => null, 'release_time' => 0];
        $url = 'https://github.com/' . self::GITHUB_REPO . '/releases.atom';

        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Vatts-App\r\n"
            ]
        ];

        $xmlData = @file_get_contents($url, false, stream_context_create($options));

        if ($xmlData === false) {
            return $default;
        }

        // [SEGURANÇA] Mitigação de XXE (XML External Entity)
        // Obriga o parser a não fazer conexões externas sob nenhuma circunstância caso o feed seja falsificado.
        libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NONET);

        if (!$xml || !isset($xml->entry)) {
            return $default;
        }

        $isCurrentCanary = $currentVersion ? str_contains(strtolower($currentVersion), 'canary') : false;

        foreach ($xml->entry as $entry) {
            $link = (string) $entry->link['href'];
            $updatedTime = strtotime((string) $entry->updated); // Pega o Timestamp da release do feed Atom!

            if (preg_match('/\/releases\/tag\/(.+)$/', $link, $matches)) {
                $tag = $matches[1];
                $isPreRelease = str_contains(strtolower($tag), 'canary');

                if (!$isCurrentCanary && $isPreRelease) {
                    continue;
                }

                return [
                    'version'      => ltrim($tag, 'v'),
                    'release_time' => $updatedTime
                ];
            }
        }

        return $default;
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