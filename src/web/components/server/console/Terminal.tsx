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
        <div className={`flex flex-col  border border-white/5 h-full w-full rounded-lg overflow-hidden shadow-[var(--card-shadow)] transition-all duration-300 ${isSuspended ? 'bg-red-950/20' : 'bg-[var(--color-console)]'}`}>

            {/* Área de Logs */}
            <div
                ref={scrollRef}
                onScroll={handleScroll}
                className={`terminal-font flex-1 p-5 text-[13px] overflow-y-auto custom-scrollbar selection:bg-[var(--color-primary)]/30 min-h-0 antialiased relative ${isSuspended ? 'bg-transparent' : 'bg-transparent'}`}
            >
                <AnimatePresence>
                    {showSpinner && (
                        <motion.div
                            key="loading-terminal"
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            className="absolute inset-0 flex flex-col items-center justify-center z-10 bg-[var(--color-console)]/50 backdrop-blur-sm rounded-lg"
                        >
                            <LoadingPage />
                        </motion.div>
                    )}
                </AnimatePresence>

                <div className="flex flex-col">
                    {logs.map((log) => (
                        <div key={log.id} className={`leading-relaxed break-all whitespace-pre-wrap mb-[1px] ${isSuspended ? 'text-red-400/80' : 'text-(--color-text-value)'}`}>
                            <Ansi>{log.line || log.message}</Ansi>
                        </div>
                    ))}

                    {/* Mensagem fixa no console quando suspenso */}
                    {isSuspended && logs.length === 0 && (
                        <div className="text-red-500 font-bold tracking-wide mt-2">
                            [SISTEMA] Conexão recusada. O servidor encontra-se suspenso.
                        </div>
                    )}
                </div>
            </div>

            {/* Input de Comandos (Estilo Inset Clean) */}
            <div className={`flex items-center gap-3 px-5 py-4 group bg-black/20 shadow-inner ${isSuspended ? 'bg-red-950/40' : ''}`}>
                <span className={`${isSuspended ? 'text-red-500/50' : 'text-[var(--color-text-sub)] group-focus-within:text-[var(--color-primary)]'} transition-colors duration-300 shrink-0`}>
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
    );
};