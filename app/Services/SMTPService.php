<?php

namespace App\Services;

use models\Settings;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class SMTPService
{

    /**
     * Envia o e-mail
     * * @param string $to Destinatário
     * @param string $subject Assunto
     * @param string $body Corpo do E-mail (HTML)
     * @return bool Retorna true se enviou com sucesso, false caso contrário
     */
    public function send(string $to, string $subject, string $body): bool
    {
        // [SEGURANÇA] Validação antecipada do e-mail para poupar recursos e evitar exceptions
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Logger::error("Tentativa de envio de e-mail para endereço inválido/malformado: {$to}");
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $settings = Settings::all();
            $mapped = [];

            foreach ($settings as $model) {
                $mapped[$model->key] = $model->value;
            }
            // Configurações do Servidor
            $mail->isSMTP();
            $mail->Host       = $mapped['smtp_host'] ?? '';
            $mail->SMTPAuth   = true;
            $mail->Username   = $mapped['smtp_user'] ?? '';
            $mail->Password   = $mapped['smtp_pass'] ?? '';
            $mail->CharSet    = 'UTF-8';

            // Define Criptografia
            $type = strtolower($mapped['type'] ?? 'tls');
            if ($type === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            // [ESTABILIDADE] Cast para (int) para garantir que a porta seja um número
            $mail->Port = (int) ($mapped['smtp_port'] ?? 587);

            // [SEGURANÇA] Prevenção de Email Header Injection (CRLF Injection)
            // Remove quebras de linha que atacantes usam para adicionar campos BCC ocultos e fazer Spam.
            $systemName = str_replace(["\r", "\n", "\0"], '', $mapped['system_name'] ?? 'Lunar Panel');
            $senderEmail = filter_var($mapped['smtp_send_email'] ?? 'nao-responda@exemplo.com', FILTER_VALIDATE_EMAIL) ?: 'nao-responda@exemplo.com';

            // Destinatários e Remetente
            $mail->setFrom($senderEmail, $systemName);
            $mail->addAddress($to);

            // Conteúdo
            $mail->isHTML();

            // [SEGURANÇA] Higieniza o assunto contra injeção de cabeçalhos
            $mail->Subject = str_replace(["\r", "\n", "\0"], '', $subject);

            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body); // Versão em texto puro

            return $mail->send();

        } catch (Exception $e) {
            // Loga o erro caso o envio falhe
            Logger::error("Erro no SMTPService ao enviar para {$to}: " . $mail->ErrorInfo);
            return false;
        }
    }
}