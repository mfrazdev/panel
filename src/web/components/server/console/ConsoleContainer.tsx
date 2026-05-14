import ServerActions from "@/web/components/server/console/Buttons";
import StatCard from "@/web/components/server/console/StatsCard";
import TerminalConsole from "@/web/components/server/console/Terminal";
import ServerCharts from "@/web/components/server/console/Charts";
import React, { useEffect } from "react";
import { useServerContext } from "@/web/contexts/ServerContext";
import { useLoading } from "@/web/components/wrappers/Wrapper";
import LoadingPage from "@/web/components/commons/LoadingPage";
import CopyOnClick from "@/web/components/commons/CopyOnClick";

const formatBytes = (bytes: number = 0) => (bytes / 1024 / 1024).toFixed(2) + ' MiB';
const formatNetwork = (bytes: number = 0) => {
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB/s';
    return (bytes / 1024 / 1024).toFixed(2) + ' MB/s';
};
const formatUptime = (ms: number = 0) => {
    if (!ms) return '0d 0h 0m';
    const seconds = Math.floor((ms / 1000) % 60);
    const minutes = Math.floor((ms / (1000 * 60)) % 60);
    const hours = Math.floor((ms / (1000 * 60 * 60)));
    return `${hours}h ${minutes}m ${seconds}s`;
};

export default function ConsoleContainer() {
    const {
        server, usage, isLoadingServer,
        allocation, usageWsStatus, consoleWsStatus
    } = useServerContext();

    const { setLoadingBar } = useLoading();

    useEffect(() => {
        if (isLoadingServer) {
            setLoadingBar(true);
        } else {
            setLoadingBar(false);
            const interval = setInterval(async () => {
                setLoadingBar(true);
                setTimeout(() => setLoadingBar(false), 1500);
            }, 20000);
            return () => clearInterval(interval);
        }
    }, [isLoadingServer, setLoadingBar]);

    if (isLoadingServer) return <div className="min-h-screen flex justify-center items-center"><LoadingPage /></div>;
    if (!server) return null;

    const isSuspended = server.suspended === 1;
    const serverStatus = isSuspended ? 'suspended' : (usage?.state || 'connecting');
    const address = allocation ? `${allocation.externalIp !== 'localhost' ? allocation.externalIp : allocation.ip}:${allocation.port}` : '---';

    const statusConfig: Record<string, { color: string; label: string; uptime: string; isAnimated: boolean }> = {
        running: { color: 'var(--color-success)', label: 'Online', uptime: formatUptime(usage?.uptimeMs), isAnimated: true },
        installing: { color: 'var(--color-warning)', label: 'Instalando', uptime: '', isAnimated: true },
        initializing: { color: 'var(--color-warning)', label: 'Iniciando', uptime: '', isAnimated: true },
        stopping: { color: 'var(--color-warning)', label: 'Desligando', uptime: '', isAnimated: true },
        stopped: { color: 'var(--color-danger)', label: 'Offline', uptime: '', isAnimated: false },
        connecting: { color: 'var(--color-text-sub)', label: 'Conectando...', uptime: '', isAnimated: true },
        suspended: { color: 'var(--color-danger)', label: 'Suspenso', uptime: '', isAnimated: false },
    };

    const currentStatus = statusConfig[serverStatus as keyof typeof statusConfig] || statusConfig.connecting;

    const isReconnecting = !isSuspended && (usageWsStatus === 'reconnecting' || consoleWsStatus === 'reconnecting');
    const isFailed = !isSuspended && (usageWsStatus === 'failed' || consoleWsStatus === 'failed');

    return (
        <>
            {/* Banners de Alerta */}
            <div className="w-full flex flex-col gap-3 empty:hidden animate-[fadeIn_0.4s_ease-out]">
                {isSuspended && (
                    <div className="mx-4 md:mx-8 xl:mx-12 mt-6 bg-red-500/10 border border-red-500/20 rounded-xl flex items-center gap-4 px-5 py-3.5 shadow-sm">
                        <div className="w-8 h-8 rounded-full bg-red-500/20 flex items-center justify-center text-red-500 shrink-0">
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth="2.5">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <span className="text-red-400 text-[13.5px] font-medium tracking-wide">
                            Este servidor está suspenso. Todos os recursos foram bloqueados pelo administrador.
                        </span>
                    </div>
                )}

                {isReconnecting && !isFailed && (
                    <div className="mx-4 md:mx-8 xl:mx-12 mt-6 bg-yellow-500/10 border border-yellow-500/20 rounded-xl flex items-center gap-4 px-5 py-3.5 shadow-sm">
                        <div className="w-8 h-8 rounded-full bg-yellow-500/20 flex items-center justify-center text-yellow-500 shrink-0">
                            <svg className="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <span className="text-yellow-400 text-[13.5px] font-medium tracking-wide">
                            Estamos com dificuldades para conectar ao node. Tentando reconectar, aguarde...
                        </span>
                    </div>
                )}

                {isFailed && (
                    <div className="mx-4 md:mx-8 xl:mx-12 mt-6 bg-red-500/10 border border-red-500/20 rounded-xl flex items-center gap-4 px-5 py-3.5 shadow-sm">
                        <div className="w-8 h-8 rounded-full bg-red-500/20 flex items-center justify-center text-red-500 shrink-0">
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth="2.5">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <span className="text-red-400 text-[13.5px] font-medium tracking-wide">
                            Falha ao conectar-se à instância após várias tentativas. Verifique sua conexão ou atualize a página.
                        </span>
                    </div>
                )}
            </div>

            {/* O flex-1 min-w-0 volta para o main, permitindo que a grid encolha! */}

            <main className={`flex-1 flex flex-col py-6 px-4 md:py-8 md:px-10 xl:px-20 overflow-x-hidden min-w-0 ${isSuspended ? 'opacity-80' : ''}`}>

                {/* Cabeçalho da Página do Servidor */}
                <div className="flex flex-col gap-4 mb-8">
                    <div className="flex flex-wrap items-center gap-5">

                        {/* Título */}
                        <h1 className="text-3xl font-black tracking-tight text-[var(--color-text-value)] truncate max-w-full">
                            {server.name}
                        </h1>

                        {/* Badge de Status Estilo Pílula */}
                        <div className="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/5 border border-white/5 shrink-0 mt-1 md:mt-0">
                            <span className="relative flex h-2 w-2">
                                {currentStatus.isAnimated && (
                                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style={{ backgroundColor: currentStatus.color }}></span>
                                )}
                                <span className="relative inline-flex rounded-full h-2 w-2" style={{ backgroundColor: currentStatus.color, boxShadow: `0 0 8px ${currentStatus.color}` }}></span>
                            </span>
                            <span className="text-[11px] uppercase font-black tracking-widest text-[var(--color-text-value)]">
                                {currentStatus.label}
                            </span>
                        </div>

                        {/* Uptime */}
                        {serverStatus === 'running' && (
                            <div className="flex items-center gap-2 border-l border-white/10 pl-5 mt-1 md:mt-0">
                                <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24" className="text-[var(--color-text-sub)]"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <span className="text-[12px] font-mono font-medium text-[var(--color-text-sub)]">
                                    {currentStatus.uptime}
                                </span>
                            </div>
                        )}
                    </div>

                    {/* Endereço de Conexão Estilo Inset */}
                    <div className="flex items-center">
                        <CopyOnClick text={address} notify={true}>
                            <div className="inline-flex items-center gap-2.5 bg-black/20 hover:bg-black/40 border border-white/5 rounded-lg px-4 py-2 transition-colors cursor-pointer group shadow-inner">
                                <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24" className="text-[var(--color-text-sub)] group-hover:text-[var(--color-primary)] transition-colors"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                <span className="text-[var(--color-text-value)] text-[13px] font-mono tracking-wide">{address}</span>
                            </div>
                        </CopyOnClick>
                    </div>
                </div>

                {/* Container flex com min-w-0 para permitir redução de espaço pela sidebar */}
                <div className="flex flex-col xl:flex-row gap-4 xl:gap-6 mb-6 min-w-0 w-full">

                    {/* Grid com breakpoints melhores para quando houver pouco espaço */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 xl:gap-6 flex-1 w-full min-w-0">
                        <StatCard
                            label="Uso de Processador"
                            value={usage && !isSuspended ? `${usage.cpu.toFixed(2)}%` : '0.00%'}
                            subValue={server.cpu === 0 ? "Ilimitado" : `${server.cpu}%`}
                            icon={<svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth="2"><rect x="4" y="4" width="16" height="16" rx="2" /><path d="M12 1v3m0 16v3M20 12h3M1 12h3" /></svg>}
                        />
                        <StatCard
                            label="Memória RAM"
                            value={isSuspended ? '0.00 MiB' : formatBytes(usage?.memory)}
                            subValue={server.ram === 0 ? "Ilimitado" : `${server.ram} MB`}
                            icon={<svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth="2"><path d="M4 21v-7m0-4V3m8 18v-9m0-4V3m8 18v-5m0-4V3M1 14h7m2-6h6m2 8h6" /></svg>}
                        />
                        <StatCard
                            label="Armazenamento"
                            value={isSuspended ? '0.00 MiB' : formatBytes(usage?.disk)}
                            subValue={server.disk === 0 ? "Ilimitado" : `${server.disk} MB`}
                            icon={<svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth="2"><path d="M22 12H2M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z" /></svg>}
                        />
                    </div>

                    <div className="flex items-center justify-center xl:justify-end shrink-0">
                        <ServerActions status={serverStatus} />
                    </div>

                </div>

                {/* Container do Terminal */}
                <div className="w-full h-[600px] mb-8 shadow-inner overflow-hidden p-1 min-w-0">
                    <TerminalConsole />
                </div>

                {/* Gráficos em tempo real */}
                {!isSuspended && <ServerCharts />}

            </main>
        </>
    )
}