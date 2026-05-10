<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha - Hight Cloud</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&family=JetBrains+Mono&display=swap" rel="stylesheet">

    @php
        $cssPath = __DIR__ . '/../public/assets/css/root.css';
        $GLOBALS['email_theme_data'] = [];

        if (file_exists($cssPath)) {
            $css = file_get_contents($cssPath);

            // Corta o CSS antes do primeiro @media para pegar apenas o modo normal/dark
            $parts = explode('@media', $css);
            $cssCore = $parts[0];

            if (preg_match_all('/:root\s*\{(.*?)\}/s', $cssCore, $matches)) {

                $allRootContent = implode(' ', $matches[1] ?? []);
                preg_match_all('/--([\w-]+)\s*:\s*([^;]+);/', $allRootContent, $vars, PREG_SET_ORDER);

                foreach ($vars as $var) {
                    $key = trim($var[1]);
                    $value = trim($var[2]);


                    // Formata RGB para e-mail
                    if (str_contains($value, 'rgb')) {
                        if (!str_contains($value, ',')) {
                            $value = preg_replace('/\s+/', ', ', $value);
                            $value = str_replace(', /,', ',', $value);
                        } else {
                            $value = str_replace('/', ',', $value);
                            $value = preg_replace('/,\s*,/', ',', $value);
                        }

                        if (substr_count($value, ',') >= 3) {
                            $value = str_replace('rgb(', 'rgba(', $value);
                        }
                    }

                    $GLOBALS['email_theme_data'][$key] = $value;
                }
            }
        }

        if (!function_exists('cssVar')) {
            function cssVar(string $name)
            {
                $val = $GLOBALS['email_theme_data'][$name] ?? '';
                return $val;
            }
        }

        $companyName = \models\Settings::get('key', 'company_name')?->value ?? 'Hight Cloud';
    @endphp

    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; }

        body {
            margin: 0;
            padding: 0;
        }

        .btn-hover:hover {
            opacity: 0.9 !important;
            transform: translateY(-1px) !important;
        }
    </style>
</head>
<body style="background-color: {{ cssVar('color-background') }}; margin: 0; padding: 0; font-family: 'Inter', Arial, sans-serif; -webkit-font-smoothing: antialiased;">

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: {{ cssVar('color-background') }}; padding: 50px 20px;">
    <tr>
        <td align="center">

            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 540px; background-color: {{ cssVar('color-secondary') }}; border-radius: 16px; overflow: hidden; box-shadow: {{ cssVar('card-shadow') }}; border: 1px solid {{ cssVar('color-terciary') }};">

                <tr>
                    <td align="center" style="background-color: {{ cssVar('color-navbar') }}; padding: 32px 20px; border-bottom: 1px solid {{ cssVar('color-terciary') }};">
                        <h1 style="color: {{ cssVar('color-primary') }}; margin: 0; font-size: 22px; font-weight: 900; letter-spacing: -0.5px; text-transform: uppercase;">
                            {{ $companyName }}
                        </h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 40px 36px;">
                        <h2 style="color: {{ cssVar('color-text-value') }}; font-size: 22px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
                            Olá, {{ $user->first_name ?? 'Usuário' }}!
                        </h2>

                        <p style="color: {{ cssVar('color-text-label') }}; font-size: 15px; font-weight: 400; line-height: 1.7; margin-top: 0; margin-bottom: 32px;">
                            Recebemos uma solicitação para redefinir a senha em <strong style="color: {{ cssVar('color-text-value') }}; font-weight: 600;">{{ $companyName }}</strong>. Se você fez essa solicitação, clique no botão abaixo para criar uma nova senha:
                        </p>

                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td align="left" style="padding-bottom: 36px;">
                                    <a class="btn-hover" href="{{ $url ?? '#' }}" target="_blank" style="display: inline-block; background-color: {{ cssVar('color-primary') }}; color: #FFFFFF; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; font-size: 15px; transition: all 0.2s ease;">
                                        Redefinir Minha Senha
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="color: {{ cssVar('color-text-sub') }}; font-size: 13px; line-height: 1.6; margin-top: 0; margin-bottom: 0;">
                            Se você não solicitou essa alteração, nenhuma ação é necessária e você pode ignorar este e-mail. Este link expira em <strong>15 minutos</strong>.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>