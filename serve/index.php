<?php

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../app/Services/LoggerService.php';
\App\Services\Logger::init();

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
        'csp' => "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data: https://ui-avatars.com https://www.gravatar.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:; script-src 'unsafe-eval' 'self' 'unsafe-inline' https://newassets.hcaptcha.com https://js.hcaptcha.com https://challenges.cloudflare.com https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; connect-src 'self' https://cdnjs.cloudflare.com ws: wss: http: https:; worker-src 'self' blob:; frame-src 'self' https://newassets.hcaptcha.com https://js.hcaptcha.com https://challenges.cloudflare.com;",
        'cross_origin_opener_policy' => 'unsafe-none',
        'cross_origin_resource_policy' => 'cross-origin'
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