import React, { useState, useEffect } from 'react';
import { useServerContext } from '@/web/contexts/ServerContext';

interface ServerActionsProps { status?: string; }

const Buttons: React.FC<ServerActionsProps> = ({ status }) => {
    const { sendServerAction } = useServerContext();
    const [pendingAction, setPendingAction] = useState<string | null>(null);

    useEffect(() => { setPendingAction(null); }, [status]);

    const handleAction = (action: string) => {
        setPendingAction(action);
        // @ts-ignore
        sendServerAction(action);
    };

    const isPending = pendingAction !== null;
    const isStartDisabled = isPending || status !== 'stopped';
    const isRestartDisabled = isPending || status === 'stopped';
    const isStopDisabled = isPending || status === 'stopped' || status === 'stopping';
    const isKillDisabled = status === 'stopped';

    return (
        <div className="flex items-center gap-4 bg-[var(--color-background)]/20 p-2 rounded-3xl shadow-inner border border-white/5 backdrop-blur-md">

            {/* Start Button (Success) */}
            <div className={`relative p-[2px] rounded-2xl transition-all duration-500 ease-out ${isStartDisabled ? 'bg-transparent shadow-none' : 'bg-gradient-to-br from-[var(--color-success)]/40 via-transparent to-transparent hover:from-[var(--color-success)]/60 shadow-lg shadow-[var(--color-success)]/10 hover:-translate-y-0.5 transform'}`}>
                <button
                    title="Iniciar"
                    disabled={isStartDisabled}
                    onClick={() => handleAction('start')}
                    className={`group relative flex items-center justify-center h-14 w-16 rounded-[14px] transition-all duration-300 ease-out overflow-hidden ${
                        isStartDisabled
                            ? 'bg-[var(--color-secondary)]/40 opacity-40 cursor-not-allowed text-[var(--color-text-sub)]'
                            : 'bg-[var(--color-secondary)]/90 backdrop-blur-xl hover:bg-[var(--color-success)]/10 active:scale-95 cursor-pointer text-[var(--color-success)]'
                    }`}
                >
                    <svg className={`relative z-10 drop-shadow-md transition-transform duration-300 ease-out ${!isStartDisabled && 'group-hover:scale-110'}`} width="26" height="26" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg>
                </button>
            </div>

            {/* Stop Button (Danger) */}
            <div className={`relative p-[2px] rounded-2xl transition-all duration-500 ease-out ${isStopDisabled ? 'bg-transparent shadow-none' : 'bg-gradient-to-br from-[var(--color-danger)]/40 via-transparent to-transparent hover:from-[var(--color-danger)]/60 shadow-lg shadow-[var(--color-danger)]/10 hover:-translate-y-0.5 transform'}`}>
                <button
                    title="Desligar"
                    disabled={isStopDisabled}
                    onClick={() => handleAction('stop')}
                    className={`group relative flex items-center justify-center h-14 w-16 rounded-[14px] transition-all duration-300 ease-out overflow-hidden ${
                        isStopDisabled
                            ? 'bg-[var(--color-secondary)]/40 opacity-40 cursor-not-allowed text-[var(--color-text-sub)]'
                            : 'bg-[var(--color-secondary)]/90 backdrop-blur-xl hover:bg-[var(--color-danger)]/10 active:scale-95 cursor-pointer text-[var(--color-danger)]'
                    }`}
                >
                    <svg className={`relative z-10 drop-shadow-md transition-transform duration-300 ease-out ${!isStopDisabled && 'group-hover:scale-110'}`} width="26" height="26" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24"><path d="M18.36 6.64a9 9 0 1 1-12.73 0M12 2v10" /></svg>
                </button>
            </div>

            {/* Restart Button (Info/Cyan) */}
            <div className={`relative p-[2px] rounded-2xl transition-all duration-500 ease-out ${isRestartDisabled ? 'bg-transparent shadow-none' : 'bg-gradient-to-br from-[var(--color-info)]/40 via-transparent to-transparent hover:from-[var(--color-info)]/60 shadow-lg shadow-[var(--color-info)]/10 hover:-translate-y-0.5 transform'}`}>
                <button
                    title="Reiniciar"
                    disabled={isRestartDisabled}
                    onClick={() => handleAction('restart')}
                    className={`group relative flex items-center justify-center h-14 w-16 rounded-[14px] transition-all duration-300 ease-out overflow-hidden ${
                        isRestartDisabled
                            ? 'bg-[var(--color-secondary)]/40 opacity-40 cursor-not-allowed text-[var(--color-text-sub)]'
                            : 'bg-[var(--color-secondary)]/90 backdrop-blur-xl hover:bg-[var(--color-info)]/10 active:scale-95 cursor-pointer text-[var(--color-info)]'
                    }`}
                >
                    <svg className={`relative z-10 drop-shadow-md transition-transform duration-300 ease-out ${!isRestartDisabled && 'group-hover:rotate-180 group-hover:scale-110'}`} width="26" height="26" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                </button>
            </div>

            {/* Separador */}
            <div className="w-[2px] h-10 bg-[var(--color-terciary)] mx-1 rounded-full opacity-40" />

            {/* Kill Button (Warning/Orange) */}
            <div className={`relative p-[2px] rounded-2xl transition-all duration-500 ease-out ${isKillDisabled ? 'bg-transparent shadow-none' : 'bg-gradient-to-br from-[var(--color-warning)]/40 via-transparent to-transparent hover:from-[var(--color-warning)]/60 shadow-lg shadow-[var(--color-warning)]/10 hover:-translate-y-0.5 transform'}`}>
                <button
                    title="Matar Processo"
                    disabled={isKillDisabled}
                    onClick={() => handleAction('kill')}
                    className={`group relative flex items-center justify-center h-14 w-16 rounded-[14px] transition-all duration-300 ease-out overflow-hidden ${
                        isKillDisabled
                            ? 'bg-[var(--color-secondary)]/40 opacity-40 cursor-not-allowed text-[var(--color-text-sub)]'
                            : 'bg-[var(--color-secondary)]/90 backdrop-blur-xl hover:bg-[var(--color-warning)]/10 active:scale-95 cursor-pointer text-[var(--color-warning)]'
                    }`}
                >
                    <svg className={`relative z-10 drop-shadow-md transition-transform duration-300 ease-out ${!isKillDisabled && 'group-hover:scale-110'}`} width="26" height="26" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24">
                        <circle cx="9" cy="12" r="1" />
                        <circle cx="15" cy="12" r="1" />
                        <path d="M8 20v2h8v-2" />
                        <path d="m12.5 17-.5-1-.5 1h1z" />
                        <path d="M16 20a2 2 0 0 0 1.56-3.25 8 8 0 1 0-11.12 0A2 2 0 0 0 8 20" />
                    </svg>
                </button>
            </div>

        </div>
    );
};

export default Buttons;