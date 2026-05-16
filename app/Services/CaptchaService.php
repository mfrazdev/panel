<?php

namespace App\Services;

use models\Settings;

class CaptchaService
{
    /**
     * Verifica se o sistema de captcha está ativo no painel.
     *
     * @return bool
     */
    public static function isActive(): bool
    {
        $system = self::getSystem();
        return in_array($system, ['turnstile', 'hcaptcha']);
    }

    /**
     * Retorna qual sistema de captcha está configurado.
     *
     * @return string (turnstile, hcaptcha ou none)
     */
    public static function getSystem(): string
    {
        $setting = Settings::get('key', 'captcha_system');
        return ($setting && !empty($setting->value)) ? $setting->value : 'none';
    }

    /**
     * Retorna a Site Key (Chave Pública) para ser usada no Frontend.
     *
     * @return string|null
     */
    public static function getSiteKey(): ?string
    {
        $setting = Settings::get('key', 'captcha_site_key');
        return $setting ? $setting->value : null;
    }

    /**
     * Retorna a Secret Key (Chave Secreta) para ser usada no Backend.
     *
     * @return string|null
     */
    private static function getSecretKey(): ?string
    {
        $setting = Settings::get('key', 'captcha_secret_key');
        return $setting ? $setting->value : null;
    }

    /**
     * Valida o token recebido do frontend (form submit) junto às APIs.
     *
     * @param string|null $token O token gerado pelo Turnstile ou hCaptcha no frontend.
     * @param string|null $ip    (Opcional) IP do usuário para validação extra.
     * @return bool              True se for válido ou desativado, False se for robô ou erro.
     */
    public static function verify(?string $token, ?string $ip = null): bool
    {
        // Se o captcha estiver desativado, permite a passagem.
        if (!self::isActive()) {
            return true;
        }

        // Se o captcha está ativo mas nenhum token foi enviado, barra.
        if (empty($token)) {
            return false;
        }

        $system = self::getSystem();
        $secretKey = self::getSecretKey();

        // Se não houver chave secreta configurada no banco, barra por segurança.
        if (empty($secretKey)) {
            return false;
        }

        // Define a URL da API dependendo do sistema escolhido
        $verifyUrl = '';
        if ($system === 'turnstile') {
            $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        } elseif ($system === 'hcaptcha') {
            $verifyUrl = 'https://hcaptcha.com/siteverify';
        } else {
            return true; // Fallback
        }

        // Monta os parâmetros para enviar via POST
        $data = [
            'secret'   => $secretKey,
            'response' => $token
        ];

        if (!empty($ip)) {
            $data['remoteip'] = $ip;
        }

        // Faz a requisição nativa no PHP usando stream context (sem depender do cURL)
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'timeout' => 10 // timeout de 10 segundos para não travar a aplicação
            ]
        ];

        $context  = stream_context_create($options);
        $response = @file_get_contents($verifyUrl, false, $context);

        // Falha na comunicação com a API do Captcha
        if ($response === false) {
            return false;
        }

        $responseData = json_decode($response, true);

        // Retorna o status de sucesso enviado pela API do serviço
        return isset($responseData['success']) && $responseData['success'] === true;
    }
}