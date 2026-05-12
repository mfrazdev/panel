<?php

namespace App\controllers\Admin;

use models\Settings;
use Vatts\Vatts;
use Vatts\Router\Request;
use Vatts\Router\Response;

class SettingsController
{
    /**
     * Centraliza os dados passados para as views
     */
    private function getViewData(Request $request, string $title, array $extraParams = []): array
    {
        $baseData = [
            'title'         => $title,
            'page_category' => 'admin',
            'page_name'     => 'settings',
            'user'          => $request->getParsed('user'),
            'backTo'        => '/admin/settings', // Volta para a própria tela de settings ao salvar
        ];

        return array_merge($baseData, $extraParams);
    }

    // ==============================================================================
    // ACTIONS DA CONTROLLER
    // ==============================================================================

    public function viewEdit(Request $request, Response $response): Response
    {
        // 1. Puxa todas as configs do banco e converte para um array chave => valor
        $settingsDb = Settings::all();
        $resource = [];

        if (is_array($settingsDb)) {
            foreach ($settingsDb as $setting) {
                $resource[$setting->key] = $setting->value;
            }
        }

        // Adiciona a variável do .env no resource que vai para a View
        $resource['company_name'] = Vatts::getEnv('COMPANY_NAME', 'Lunar Panel');

        // 2. Mapeia os campos separados por Tabs
        $map = [
            'Geral' => [
                [
                    'label' => 'Nome da Empresa',
                    'key'   => 'company_name',
                    'type'  => 'text',
                    'desc'  => 'Este é o nome utilizado em todo o painel e nos e-mails enviados aos clientes.',
                    'required' => true,
                    'default' => 'Lunar Panel'
                ]
            ],
            'E-mail (SMTP)' => [
                [
                    'label' => 'Host SMTP',
                    'key'   => 'smtp_host',
                    'type'  => 'text',
                    'desc'  => 'Endereço do servidor de disparo de e-mails.',
                    'default' => 'smtp.exemplo.com'
                ],
                [
                    'label' => 'Porta SMTP',
                    'key'   => 'smtp_port',
                    'type'  => 'text',
                    'default' => '587'
                ],
                [
                    'label' => 'Usuário SMTP',
                    'key'   => 'smtp_user',
                    'type'  => 'text',
                    'default' => 'root@root.com'
                ],
                [
                    'label' => 'Email de envio',
                    'key'   => 'smtp_send_email',
                    'type'  => 'text',
                    'default' => 'root@root.com'
                ],
                [
                    'label' => 'Senha SMTP',
                    'key'   => 'smtp_pass',
                    'type'  => 'password',
                ],
                [
                    'label' => 'Tipo de conexão',
                    'key' => 'type',
                    'type' => 'select',
                    'options' => [
                        'tls' => 'TLS',
                        'ssl' => 'SSL'
                    ],
                    'desc' => 'Se sim, o servidor não poderá ser iniciado, modificado ou receber comandos.'
                ],
            ]
        ];

        $viewData = [
            'resource'   => $resource,
            'map'        => $map,
            'canDelete'  => false,
            'force_tabs' => true
        ];

        return $response->view('resources.edit_create', $this->getViewData($request, "Configurações do Painel", $viewData));
    }

    public function edit(Request $request, Response $response): Response
    {
        $body = $request->getBody();

        // Salva as configurações de banco de dados
        $allowedDbKeys = [
            'sftp_host', 'sftp_port',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_send_email', 'type'
        ];

        foreach ($allowedDbKeys as $key) {
            if (isset($body[$key]) && $body[$key] !== '') {
                $setting = Settings::get('key', $key);

                if ($setting) {
                    $setting->value = $body[$key];
                    $setting->save();
                } else {
                    $newSetting = new Settings();
                    $newSetting->key = $key;
                    $newSetting->value = $body[$key];
                    $newSetting->save();
                }
            }
        }

        // Salva a configuração de .env separadamente
        if (isset($body['company_name'])) {
            $this->updateEnvFile(['COMPANY_NAME' => $body['company_name']]);
        }

        return $response->setFlash(['success' => 'As configurações foram salvas com sucesso!'])
            ->redirect("/admin/settings");
    }

    /**
     * Atualiza apenas as chaves informadas no arquivo .env
     */
    private function updateEnvFile(array $data): void
    {
        $envPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . '.env';

        if (!file_exists($envPath)) {
            touch($envPath);
        }

        $envContent = file_get_contents($envPath);
        $lines = explode("\n", $envContent);
        $newLines = [];
        $updatedKeys = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || str_starts_with($trimmed, '#')) {
                $newLines[] = $line;
                continue;
            }

            $parts = explode('=', $line, 2);
            $key = trim($parts[0]);

            if (array_key_exists($key, $data)) {
                $val = (string) $data[$key];
                if (preg_match('/\s/', $val)) {
                    $val = '"' . $val . '"';
                }
                $newLines[] = "{$key}={$val}";
                $updatedKeys[] = $key;
            } else {
                $newLines[] = $line;
            }
        }

        foreach ($data as $key => $val) {
            if (!in_array($key, $updatedKeys)) {
                $val = (string) $val;
                if (preg_match('/\s/', $val)) {
                    $val = '"' . $val . '"';
                }
                $newLines[] = "{$key}={$val}";
            }
        }

        file_put_contents($envPath, implode("\n", $newLines));
    }
}