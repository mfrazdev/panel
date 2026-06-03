import React, { useLayoutEffect, useRef, useState, useEffect } from 'react';
import Ansi from 'ansi-to-react';
import { useServerContext } from '@/web/contexts/ServerContext';
import LoadingPage from "@/web/components/commons/LoadingPage";
import { AnimatePresence, motion } from "framer-motion";

export default function Terminal() {
    const scrollRef = useRef<HTMLDivElement>(null);
    const { logs, sendCommand, consoleWsStatus, server } = useServerContext();
    const [commandInput, setCommandInput] = useState('');
    const shouldScrollRef = useRef(true);

    // Estados para o histórico de comandos
    const [history, setHistory] = useState<string[]>([]);
    const [historyIndex, setHistoryIndex] = useState(-1);

    const isSuspended = server?.suspended === 1;

    // Carrega o histórico do localStorage específico do servidor ao iniciar ou trocar de servidor
    useEffect(() => {
        if (!server?.id) return; // Garante que temos o ID do servidor

        const storageKey = `terminal_history_${server.id}`;
        const savedHistory = localStorage.getItem(storageKey);

        if (savedHistory) {
            setHistory(JSON.parse(savedHistory));
        } else {
            setHistory([]); // Limpa o estado se o servidor não tiver histórico
        }
        setHistoryIndex(-1); // Reseta a navegação ao trocar de servidor
    }, [server?.id]);

    useLayoutEffect(() => {
        if (!scrollRef.current || !shouldScrollRef.current) return;
        scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
    }, [logs]);

    const handleScroll = () => {
        if (!scrollRef.current) return;
        const { scrollTop, scrollHeight, clientHeight } = scrollRef.current;
        shouldScrollRef.current = scrollHeight - scrollTop - clientHeight < 60;
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter' && commandInput.trim()) {
            const currentCommand = commandInput.trim();
            sendCommand(currentCommand);

            // Salva no histórico (evitando duplicatas seguidas e limitando a 50 comandos)
            const newHistory = [currentCommand, ...history.filter(cmd => cmd !== currentCommand)].slice(0, 50);
            setHistory(newHistory);

            // Salva no localStorage com o ID do servidor
            if (server?.id) {
                localStorage.setItem(`terminal_history_${server.id}`, JSON.stringify(newHistory));
            }

            setHistoryIndex(-1); // Reseta a navegação
            setCommandInput('');
            shouldScrollRef.current = true;
            setTimeout(() => {
                if (scrollRef.current) scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
            }, 10);
        }
        else if (e.key === 'ArrowUp') {
            e.preventDefault(); // Previne o cursor de ir pro início do texto
            if (history.length > 0) {
                const nextIndex = Math.min(historyIndex + 1, history.length - 1);
                setHistoryIndex(nextIndex);
                setCommandInput(history[nextIndex]);
            }
        }
        else if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (historyIndex > 0) {
                const nextIndex = historyIndex - 1;
                setHistoryIndex(nextIndex);
                setCommandInput(history[nextIndex]);
            } else if (historyIndex === 0) {
                setHistoryIndex(-1);
                setCommandInput(''); // Limpa o input se voltar tudo pra baixo
            }
        }
    };

    // Esconde o spinner se o servidor estiver suspenso
    const showSpinner = !isSuspended && (consoleWsStatus === 'connecting' || consoleWsStatus === 'reconnecting' || (logs.length === 0 && consoleWsStatus === 'connected'));

    return (
        /* Glow Wrapper com a Borda Fake animada */
        <div className={`group relative p-[4px] rounded-2xl transition-all duration-500 ease-out h-full w-full flex flex-col ${
            isSuspended
                ? 'bg-gradient-to-br from-[var(--color-danger)]/40 via-[var(--color-secondary)] to-transparent hover:from-[var(--color-danger)]/60 hover:shadow-[var(--color-danger)]/10'
                : 'bg-gradient-to-br from-[var(--color-terciary)] via-[var(--color-secondary)] to-transparent'
        }`}>

            {/* Inner Container - Fundo Escuro Limpo sem bordas reais */}
            <div className={`relative flex flex-col flex-1 rounded-[14px] overflow-hidden transition-colors duration-500 ease-out backdrop-blur-xl ${
                isSuspended ? 'bg-[var(--color-danger)]/10' : 'bg-[var(--color-console)]'
            }`}>

                {/* Área de Logs */}
                <div
                    ref={scrollRef}
                    onScroll={handleScroll}
                    className="terminal-font flex-1 p-5 text-[13px] overflow-y-auto custom-scrollbar selection:bg-[var(--color-primary)]/30 min-h-0 antialiased relative bg-transparent"
                >
                    <AnimatePresence>
                        {showSpinner && (
                            <motion.div
                                key="loading-terminal"
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                exit={{ opacity: 0 }}
                                className="absolute inset-0 flex flex-col items-center justify-center z-10 bg-[var(--color-console)]/50 backdrop-blur-sm rounded-[14px]"
                            >
                                <LoadingPage />
                            </motion.div>
                        )}
                    </AnimatePresence>

                    <div className="flex flex-col">
                        {logs.map((log, index) => (
                            <div key={`${log.id}-${index}`} className={`leading-relaxed break-all whitespace-pre-wrap mb-[1px] ${isSuspended ? 'text-[var(--color-danger)]/80' : 'text-[var(--color-text-value)]'}`}>
                                <Ansi>{log.line || log.message}</Ansi>
                            </div>
                        ))}

                        {/* Mensagem fixa no console quando suspenso */}
                        {isSuspended && logs.length === 0 && (
                            <div className="text-[var(--color-danger)] font-bold tracking-wide mt-2">
                                [SISTEMA] Conexão recusada. O servidor encontra-se suspenso.
                            </div>
                        )}
                    </div>
                </div>

                {/* Input de Comandos (Estilo Inset Clean adaptado pro Root) */}
                <div className={`flex items-center gap-3 px-5 py-4 relative z-10 transition-colors duration-500 ease-out ${
                    isSuspended ? 'bg-[var(--color-danger)]/20' : 'bg-[var(--color-console-command)]'
                }`}>
                    <span className={`${isSuspended ? 'text-[var(--color-danger)]/50' : 'text-[var(--color-text-sub)] focus-within:text-[var(--color-primary)]'} transition-colors duration-300 shrink-0`}>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        value={commandInput}
                        onChange={(e) => setCommandInput(e.target.value)}
                        onKeyDown={handleKeyDown}
                        disabled={consoleWsStatus !== 'connected' || isSuspended}
                        placeholder={
                            isSuspended
                                ? "Acesso negado: Servidor suspenso."
                                : (consoleWsStatus === 'connected' ? "Digite um comando..." : "Console desconectado.")
                        }
                        className="terminal-font flex-1 bg-transparent border-none outline-none text-[var(--color-text-value)] text-[14px] placeholder:text-[var(--color-text-sub)]/50 disabled:opacity-50 transition-colors"
                    />
                </div>

            </div>
        </div>
    );
};