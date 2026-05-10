<?php

namespace App\controllers\Admin;

use models\Settings;
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
        // Isso simula um "Resource" único para a sua view_resources funcionar
        $settingsDb = Settings::all();
        $resource = [];

        if (is_array($settingsDb)) {
            foreach ($settingsDb as $setting) {
                $resource[$setting->key] = $setting->value;
            }
        }

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
            'resource'   => $resource, // Manda o array construído
            'map'        => $map,
            'canDelete'  => false,     // Desativa botão de excluir (são configs globais)
            'force_tabs' => true       // Opcional, caso queira forçar layout em tabs
        ];

        return $response->view('resources.edit_create', $this->getViewData($request, "Configurações do Painel", $viewData));
    }

    public function edit(Request $request, Response $response): Response
    {
        $body = $request->getBody();

        // 1. Chaves mapeadas permitidas para salvar (segurança contra keys injetadas)
        $allowedKeys = [
            'company_name',
            'sftp_host', 'sftp_port',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_send_email', 'type'
        ];

        // 2. Itera sobre o array e atualiza/cria no banco de dados item por item
        foreach ($allowedKeys as $key) {
            if (isset($body[$key]) && $body[$key] !== '') {
                // Busca a config pela 'key'
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

        return $response->setFlash(['success' => 'As configurações foram salvas com sucesso!'])
            ->redirect("/admin/settings");
    }
}