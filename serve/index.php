<?php

require __DIR__ . '/../vendor/autoload.php';

// Ativa o log de erros
ini_set('log_errors', 1);

// Define o caminho do arquivo (pode ser relativo ao script ou absoluto)
ini_set('error_log', __DIR__ . '/meus_erros.log');

// Opcional: mostra na tela também para não ter dúvida
ini_set('display_errors', 1);
error_reporting(E_ALL);

use Vatts\Vatts;


$project = dirname(__DIR__);
Vatts::loadEnv($project);

require_once __DIR__ . '/../app/Utils/DatabaseBooter.php';

// Busca apenas o company_name do .env para não estourar a conexão no Lazy Load
$companyName = Vatts::getEnv('COMPANY_NAME', 'Lunar Panel');

// Exemplo mínimo de inicialização: passa project_path, config de DB e security
$app = Vatts::init([
    'project_path' => $project,
    'security' => [
        // Atualizando o connect-src para permitir WebSockets e requisições HTTP/HTTPS externas (necessário para as nodes)
        'csp' => "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data: https://ui-avatars.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; connect-src 'self' https://cdnjs.cloudflare.com ws: wss: http: https:; worker-src 'self' blob:;"
    ],
    'frontend_tags' => [
        "
<script>
window.PanelSettings = {
    name: '$companyName'
}
</script>
"
    ]
]);

// Inicia o servidor
$app->run();