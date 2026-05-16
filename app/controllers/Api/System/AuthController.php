<?php

namespace App\controllers\Api\System;

use App\Services\Logger;
use App\Services\SMTPService;
use App\Utils\ApiHelper;
use models\Codes;
use models\User;
use Vatts\Router\Request;
use Vatts\Router\Response;
use Vatts\Utils\BladeConfig;
use Vatts\Vatts;

class AuthController
{
    public static function verifyCode(Request $request, Response $response): Response
    {
        $code = trim($request->getQuery()['code'] ?? '');
        if ($code === '') {
            return $response->json(['error' => 'O código é obrigatório.'])->status(400);
        }
        $codedb = Codes::get('code', $code);
        if (!$codedb) {
            return $response->json(['error' => 'Código inválido ou expirado.'])->status(400);
        }

        // Validação: Expira em 15 minutos
        $createdAt = strtotime($codedb->created_at);
        if ($createdAt < strtotime('-15 minutes')) {
            $codedb->delete();
            return $response->json(['error' => 'O link de recuperação expirou (limite de 15 minutos).'])->status(400);
        }

        $user = User::get($codedb->userId);
        if (!$user) {
            return $response->json(['error' => 'Usuário associado ao código não encontrado.'])->status(404);
        }

        return $response->json(['success' => true, 'email' => $codedb->email, 'userName' => $user->first_name . ' ' . $user->last_name]);
    }

    public static function changePassword(Request $request, Response $response): Response
    {
        $code = trim($request->getQuery()['code'] ?? '');
        $password = trim($request->getBody()['password'] ?? '');
        $confirmPassword = trim($request->getBody()['confirmPassword'] ?? '');

        if ($code === '') {
            return $response->json(['error' => 'O código é obrigatório.'])->status(400);
        }
        if ($password === '' || $confirmPassword === '') {
            return $response->json(['error' => 'Os campos de senha são obrigatórios.'])->status(400);
        }
        if ($password !== $confirmPassword) {
            return $response->json(['error' => 'As senhas não coincidem.'])->status(400);
        }
        if (mb_strlen($password) < 8) {
            return $response->json(['error' => 'A senha deve ter no mínimo 8 caracteres.'])->status(400);
        }

        $codedb = Codes::get('code', $code);
        if (!$codedb) {
            return $response->json(['error' => 'Código inválido ou expirado.'])->status(400);
        }

        $createdAt = strtotime($codedb->created_at);
        if ($createdAt < strtotime('-15 minutes')) {
            $codedb->delete();
            return $response->json(['error' => 'O link de recuperação expirou.'])->status(400);
        }

        $user = User::find($codedb->userId);
        if (!$user) {
            return $response->json(['error' => 'Usuário associado ao código não encontrado.'])->status(404);
        }

        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->save();
        $codedb->delete();

        return $response->json(['success' => true]);
    }

    public static function sendRecoveryEmail(Request $request, Response $response): Response
    {
        $email = trim($request->getBody()['email'] ?? '');

        if ($email === '') {
            return $response->json(['error' => 'O campo de e-mail é obrigatório.'])->status(400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $response->json(['error' => 'E-mail inválido.'])->status(400);
        }

        $user = User::get('email', $email);
        if (!$user) {
            return $response->json(['success' => true]);
        }

        $existingCode = Codes::get('userId', $user->id);

        if ($existingCode) {
            $lastSent = strtotime($existingCode->created_at);
            if ($lastSent > strtotime('-1 minute')) {
                return $response->json(['error' => 'Aguarde 1 minuto para solicitar uma nova recuperação.'])->status(429);
            }
            $existingCode->delete();
        }

        try {
            $code = ApiHelper::generateRandomString(64);
            $token = new Codes();
            $token->userId = $user->id;
            $token->email = $email;
            $token->code = $code;
            $token->save();
        } catch (\Exception $e) {
            Logger::error("Erro ao criar código de recuperação no DB para {$email}: " . $e->getMessage());
            return $response->json(['error' => 'Erro interno ao processar solicitação.'])->status(500);
        }

        $response->json(['success' => true]);
        ApiHelper::emitAndDisconnect($response);

        // BACKGROUND PROCESSING
        try {
            $smtpService = new SMTPService();
            $linkRecuperacao = Vatts::getEnv('URL') . "/auth/recovery?code={$code}";
            $assunto = "Recuperação de Senha";

            $corpo = BladeConfig::get()->run("emails.recovery", [
                "url" => $linkRecuperacao,
                "user" => $user
            ]);

            $smtpService->send($user->email, $assunto, $corpo);

        } catch (\Exception $e) {
            Logger::error("Erro no background (Email/Blade) para {$email}: " . $e->getMessage());
        }

        exit;
    }
}