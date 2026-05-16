<?php

namespace App\Services;

class Logger
{
    // Diretório base para os logs (pode ser ajustado conforme a estrutura do seu projeto)
    private static $logDir = __DIR__ . '/../../storage/logs';
    // Nome do arquivo de log principal
    private static $logFile = 'app.log';

    /**
     * Inicializa o Logger e intercepta erros nativos do PHP.
     */
    public static function init(): void
    {
        // Garante que o diretório de logs exista
        self::ensureLogDirectoryExists();

        $logPath = self::getLogFilePath();

        // 1. Configuração padrão do PHP (Faz o error_log() nativo ir para o nosso arquivo)
        ini_set('log_errors', 1);
        ini_set('error_log', $logPath);

        // 2. Intercepta erros do PHP (Warnings, Notices, etc) para usar a NOSSA formatação
        set_error_handler(function ($severity, $message, $file, $line) {
            // Ignora se o erro foi suprimido pelo operador @ no código
            if (!(error_reporting() & $severity)) {
                return;
            }

            $level = self::getErrorLevelName($severity);
            self::writeLog($level, $message, ['file' => $file, 'line' => $line]);

            // Retorna false para permitir que o PHP continue seu fluxo normal após logar
            return false;
        });

        // 3. Intercepta Exceções não tratadas (Fatal Errors)
        set_exception_handler(function (\Throwable $e) {
            self::error("Uncaught Exception: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        });
    }

    /**
     * Traduz o código numérico de erro do PHP para um texto legível.
     */
    private static function getErrorLevelName(int $severity): string
    {
        $levels = [
            E_ERROR => 'E_ERROR', E_WARNING => 'E_WARNING', E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE', E_CORE_ERROR => 'E_CORE_ERROR', E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR', E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR', E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE', E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR', E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED'
        ];
        return $levels[$severity] ?? 'E_UNKNOWN';
    }

    /**
     * Registra uma mensagem de informação.
     *
     * @param string $message A mensagem a ser registrada.
     * @param array $context Dados adicionais para contexto.
     */
    public static function info(string $message, array $context = [])
    {
        self::writeLog('INFO', $message, $context);
    }

    /**
     * Registra uma mensagem de aviso.
     *
     * @param string $message A mensagem a ser registrada.
     * @param array $context Dados adicionais para contexto.
     */
    public static function warning(string $message, array $context = [])
    {
        self::writeLog('WARNING', $message, $context);
    }

    /**
     * Registra uma mensagem de erro.
     *
     * @param string $message A mensagem a ser registrada.
     * @param array $context Dados adicionais para contexto.
     */
    public static function error(string $message, array $context = [])
    {
        self::writeLog('ERROR', $message, $context);
    }

    /**
     * Escreve a mensagem no arquivo de log.
     *
     * @param string $level Nível do log (INFO, WARNING, ERROR, etc.)
     * @param string $message Mensagem do log.
     * @param array $context Dados de contexto (convertidos para JSON).
     */
    private static function writeLog(string $level, string $message, array $context = [])
    {
        self::ensureLogDirectoryExists();

        $logPath = self::getLogFilePath();

        // Formata a data e hora
        $date = date('Y-m-d H:i:s');

        // Formata o contexto como JSON se não estiver vazio
        $contextString = !empty($context) ? ' ' . json_encode($context) : '';

        // Monta a linha de log final
        // Formato: [Data] NIVEL: Mensagem {"contexto":"dados"}
        $logLine = sprintf("[%s] %s: %s%s\n", $date, $level, $message, $contextString);

        // Escreve no arquivo de forma segura (FILE_APPEND para não sobrescrever, LOCK_EX para evitar problemas de concorrência)
        file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Retorna o caminho completo para o arquivo de log.
     */
    private static function getLogFilePath(): string
    {
        return self::$logDir . '/' . self::$logFile;
    }

    /**
     * Cria o diretório de logs se ele não existir.
     */
    private static function ensureLogDirectoryExists()
    {
        if (!is_dir(self::$logDir)) {
            // Cria o diretório com permissões 0777 (recursivo)
            mkdir(self::$logDir, 0777, true);
        }
    }
}