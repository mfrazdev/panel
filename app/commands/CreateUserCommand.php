<?php

namespace App\commands;

use App\Utils\SchedulerUtils;
use Vatts\Console\Command;
use Vatts\Console\Terminal\Console;
use models\Server;
use Vatts\Vatts;

class CreateUserCommand extends Command
{
    public function getName(): string
    {
        return 'make:user';
    }

    public function getDescription(): string
    {
        return 'Cria um usuário manualmente via terminal. Útil para criar admins ou para testes.';
    }

    public function askName(): string
    {
        return Console::ask('Nome do usuário');
    }
    public function askEmail(): string
    {
        $email = Console::ask('Email do usuário');
        // Valida o formato do email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Console::error('Email inválido. Por favor, insira um email válido.');
            return $this->askEmail(); // Chama recursivamente até obter um email válido
        }
        return $email;
    }
    public function askPassword(): string
    {
        $password = Console::ask('Senha do usuário (mínimo 6 caracteres)');
        if(strlen($password) < 6) {
            Console::error('A senha deve ter pelo menos 6 caracteres. Tente novamente.');
            return $this->askPassword(); // Chama recursivamente até obter uma senha válida
        }
        return $password;
    }
    public function askIsAdmin(): bool
    {
        return $isAdmin = Console::confirm('Este usuário é um administrador? (y/n)');
    }

    public function handle(array $args): void
    {
        Vatts::loadEnv(__DIR__ . '/../../');
        require_once __DIR__ . '/../Utils/DatabaseBooter.php';
        try {
            $name = $this->askName();
            $email = $this->askEmail();
            $password = $this->askPassword();
            $isAdmin = $this->askIsAdmin();

            $user = new \models\User();
            $user->name = $name;
            $user->email = $email;
            $user->password = password_hash($password, PASSWORD_BCRYPT);
            $user->role = $isAdmin ? 'admin' : 'user';
            $user->save();

            Console::success("Usuário '{$name}' criado com sucesso! Email: {$email}");
        } catch (\Throwable $e) {
            Console::error('Erro ao criar usuário: ' . $e->getMessage());
        }
    }
}