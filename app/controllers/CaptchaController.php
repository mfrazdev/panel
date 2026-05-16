<?php

namespace App\controllers;

use App\Services\CaptchaService;
use Vatts\Router\Request;
use Vatts\Router\Response;

class CaptchaController
{
    /**
     * Retorna as configurações públicas do Captcha para o Frontend.
     * O frontend usará essa rota para saber se deve exibir o widget de captcha
     * e qual chave pública (Site Key) utilizar na renderização.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getConfig(Request $request, Response $response): Response
    {
        $isActive = CaptchaService::isActive();
        $system = CaptchaService::getSystem();
        $siteKey = CaptchaService::getSiteKey();

        return $response->json([
            'success'   => true,
            'is_active' => $isActive,
            'system'    => $system,
            'site_key'  => $isActive ? $siteKey : null
        ]);
    }

    /**
     * Valida um token de captcha recebido avulso via requisição da API.
     * Útil se você fizer uma verificação em duas etapas no frontend via AJAX.
     *
     * Nota: Na maioria dos casos, você não usará essa rota, e sim chamará
     * CaptchaService::verify($token) direto na action de Login ou Registro.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function validateToken(Request $request, Response $response): Response
    {
        // Se o captcha não estiver ativo, já retorna sucesso automaticamente
        if (!CaptchaService::isActive()) {
            return $response->json([
                'success' => true,
                'message' => 'Captcha desativado.'
            ]);
        }

        // Pega o body da requisição (JSON ou form-data)
        $body = $request->getBody();

        // O nome do campo pode variar dependendo de como você envia do frontend.
        // Geralmente é 'cf-turnstile-response' ou 'h-captcha-response',
        // mas aqui vamos assumir que o frontend padronizou e enviou como 'captcha_token'
        $token = $body['captcha_token'] ?? null;

        if (empty($token)) {
            return $response->json([
                'success' => false,
                'message' => 'Token do captcha não foi fornecido.'
            ])->status(400); // Bad Request
        }

        // Tenta capturar o IP real do usuário para uma camada extra de segurança (opcional)
        $userIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

        // Faz a verificação na API da Cloudflare ou hCaptcha
        $isValid = CaptchaService::verify($token, $userIp);

        if ($isValid) {
            return $response->json([
                'success' => true,
                'message' => 'Captcha validado com sucesso!'
            ]);
        }

        // Falha na verificação
        return $response->json([
            'success' => false,
            'message' => 'Falha na verificação do Captcha. Você é um robô?'
        ])->status(400); // Bad Request
    }
}