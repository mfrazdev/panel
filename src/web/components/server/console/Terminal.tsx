import React, { useLayoutEffect, useRef, useState } from 'react';
import Ansi from 'ansi-to-react';
import { useServerContext } from '@/web/contexts/ServerContext';
import LoadingPage from "@/web/components/commons/LoadingPage";
import { AnimatePresence, motion } from "framer-motion";

export default function Terminal() {
    const scrollRef = useRef<HTMLDivElement>(null);
    const { logs, sendCommand, consoleWsStatus, server } = useServerContext();
    const [commandInput, setCommandInput] = useState('');
    const shouldScrollRef = useRef(true);

    const isSuspended = server?.suspended === 1;

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
            sendCommand(commandInput);
            setCommandInput('');
            shouldScrollRef.current = true;
            setTimeout(() => {
                if (scrollRef.current) scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
            }, 10);
        }
    };

    // Esconde o spinner se o servidor estiver suspenso
    const showSpinner = !isSuspended && (consoleWsStatus === 'connecting' || consoleWsStatus === 'reconnecting' || (logs.length === 0 && consoleWsStatus === 'connected'));

    return (
        <div className={`flex flex-col h-full w-full rounded-xl overflow-hidden backdrop-blur-md shadow-(--card-shadow) transition-all duration-300 ${isSuspended ? 'bg-red-950/20' : 'bg-(--color-console)'}`}>
            {/* Área de Logs */}
            <div
                ref={scrollRef}
                onScroll={handleScroll}
                className={`terminal-font flex-1 p-5 text-[13px] overflow-y-auto custom-scrollbar selection:bg-(--color-primary)/30 min-h-0 antialiased ${isSuspended ? 'bg-transparent' : 'bg-(--color-console)'}`}
            >
                <AnimatePresence>
                    {showSpinner && (
                        <motion.div
                            key="loading-terminal"
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            className="absolute inset-0 flex flex-col items-center justify-center z-10"
                        >
                            <LoadingPage />
                        </motion.div>
                    )}
                </AnimatePresence>

                <div className="flex flex-col">
                    {logs.map((log) => (
                        <div key={log.id} className={`leading-relaxed break-all whitespace-pre-wrap mb-[1px] ${isSuspended ? 'text-red-400/80' : 'text-(--color-text-label)'}`}>
                            <Ansi>{log.line || log.message}</Ansi>
                        </div>
                    ))}

                    {/* Mensagem fixa no console quando suspenso */}
                    {isSuspended && logs.length === 0 && (
                        <div className="text-red-500 font-bold tracking-wide">
                            [SISTEMA] Conexão recusada. O servidor encontra-se suspenso.
                        </div>
                    )}
                </div>
            </div>

            {/* Input de Comandos */}
            <div className={`flex items-center gap-3 px-5 py-4 group focus-within:border-(--color-primary)/30 ${isSuspended ? 'bg-red-950/40' : 'bg-(--color-console-command)'}`}>
                <span className={`${isSuspended ? 'text-red-500/50' : 'text-(--color-primary) group-focus-within:text-(--color-secondary)'} transition-colors`}>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3">
                        <polyline points="13 17 18 12 13 7" /><polyline points="6 17 11 12 6 7" />
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
                    className="terminal-font flex-1 bg-transparent border-none outline-none text-(--color-text-label) text-[14px] placeholder:text-(--color-text-sub) disabled:opacity-50"
                />
            </div>
        </div>
    );
};