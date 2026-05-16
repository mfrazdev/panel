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

            $mail->Port = $mapped['smtp_port'] ?? 587;

            // Destinatários e Remetente
            $mail->setFrom($mapped['smtp_send_email'] ?? 'nao-responda@exemplo.com', $mapped['system_name'] ?? 'Lunar Panel');
            $mail->addAddress($to);

            // Conteúdo
            $mail->isHTML();
            $mail->Subject = $subject;
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