<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha - Hight Cloud</title>
    <!-- Fonte Inter importada para clientes de e-mail que suportam -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        /* Reset básico para e-mails */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; }
        body { margin: 0; padding: 0; background-color: #382A4C; font-family: 'Inter', Arial, sans-serif; }
    </style>
</head>
<body style="background-color: #382A4C; margin: 0; padding: 0; font-family: 'Inter', Arial, sans-serif; color: #FFFFFF;">

<!-- Fundo principal (bgBase) -->
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #382A4C; padding: 40px 20px;">
    <tr>
        <td align="center">

            <!-- Card Principal (cards) -->
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #4A3863; border-radius: 16px; overflow: hidden; box-shadow: 0 12px 30px rgba(0,0,0,0.45);">

                <!-- Header (Sidebar/Navbar bg) -->
                <tr>
                    <td align="center" style="background-color: #281E39; padding: 30px 20px;">
                        @php
                            $companyName = \models\Settings::get('key', 'company_name')?->value ?? 'Hight Cloud';
                        @endphp
                                <!-- Logo / Nome da Empresa -->
                        <h1 style="color: #DF5FFF; margin: 0; font-size: 24px; font-weight: 900; letter-spacing: -1px; text-transform: uppercase;">
                            {{ $companyName }}
                        </h1>
                    </td>
                </tr>

                <!-- Corpo do E-mail -->
                <tr>
                    <td style="padding: 40px 30px;">
                        <h2 style="color: #FFFFFF; font-size: 20px; font-weight: 700; margin-top: 0; margin-bottom: 20px;">
                            Olá, {{ $user->first_name ?? 'Usuário' }}!
                        </h2>

                        <p style="color: #E6EDF3; font-size: 15px; line-height: 1.6; margin-top: 0; margin-bottom: 30px;">
                            Recebemos uma solicitação para redefinir a senha da sua conta na <strong>{{ $companyName }}</strong>. Se você fez essa solicitação, clique no botão abaixo para criar uma nova senha:
                        </p>

                        <!-- Botão de Ação -->
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td align="center" style="padding-bottom: 30px;">
                                    <a href="{{ $url ?? '#' }}" target="_blank" style="display: inline-block; background-color: #DF5FFF; color: #FFFFFF; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 15px; box-shadow: 0 4px 15px rgba(223, 95, 255, 0.3);">
                                        Redefinir Minha Senha
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <!-- Aviso -->
                        <p style="color: #9198AA; font-size: 13px; line-height: 1.5; margin-top: 0; margin-bottom: 0;">
                            Se você não solicitou a redefinição de senha, nenhuma ação adicional é necessária e você pode ignorar este e-mail. Este link expirará em 60 minutos.
                        </p>

                        <!-- Linha divisória -->
                        <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.05); margin: 30px 0;">

                        <!-- Fallback de Link -->
                        <p style="color: #9198AA; font-size: 12px; line-height: 1.5; margin-top: 0; margin-bottom: 0;">
                            Se estiver com problemas para clicar no botão "Redefinir Minha Senha", copie e cole a URL abaixo no seu navegador:
                            <br><br>
                            <a href="{{ $url ?? '#' }}" style="color: #DF5FFF; word-break: break-all; text-decoration: none;">
                                {{ $url ?? 'https://hightcloud.com/password/reset/token' }}
                            </a>
                        </p>
                    </td>
                </tr>

                <!-- Footer (Navbar bg) -->
                <tr>
                    <td align="center" style="background-color: #1B1327; padding: 24px 20px; color: #9198AA; font-size: 12px; font-weight: 500;">
                        &copy; {{ date('Y') }} {{ $companyName }}. Todos os direitos reservados.
                    </td>
                </tr>

            </table>
            <!-- Fim do Card -->

        </td>
    </tr>
</table>

</body>
</html>