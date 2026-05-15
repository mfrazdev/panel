import React from 'react';
import { ServerData } from "@/web/types";
import { Link } from "vatts/react";

interface ServerRowProps {
    // Tipagem atualizada para aceitar o objeto user e o ownerId
    server: ServerData & {
        description?: string;
        user?: { id: number; first_name: string; email: string };
        ownerId?: string | number;
    };
    status?: string;
    stats?: {
        cpu: number;
        ram: number;
        disk: number;
    };
    allocation?: {
        ip: string;
        externalIp: string;
        port: number;
    };
    isAdminView?: boolean; // Nova prop para saber se estamos no modo global
}

const STATUS_CONFIG: Record<string, { color: string; label: string; isAnimated: boolean }> = {
    running: { color: 'var(--color-success)', label: 'Online', isAnimated: true },
    initializing: { color: 'var(--color-warning)', label: 'Iniciando', isAnimated: true },
    starting: { color: 'var(--color-warning)', label: 'Iniciando', isAnimated: true },
    installing: { color: 'var(--color-warning)', label: 'Instalando', isAnimated: true },
    stopping: { color: 'var(--color-warning)', label: 'Desligando', isAnimated: true },
    stopped: { color: 'var(--color-danger)', label: 'Offline', isAnimated: false },
    offline: { color: 'var(--color-danger)', label: 'Offline', isAnimated: false },
    suspended: { color: 'var(--color-danger)', label: 'Suspenso', isAnimated: false },
    conectando: { color: 'var(--color-text-sub)', label: 'Conectando...', isAnimated: true },
};

const ServerRow: React.FC<ServerRowProps> = ({ server, status = 'offline', stats, allocation, isAdminView = false }) => {
    const currentStatus = STATUS_CONFIG[status.toLowerCase()] || STATUS_CONFIG.stopped;
    const isConnecting = status.toLowerCase() === 'conectando';

    const address = allocation
        ? `${allocation.externalIp !== 'localhost' ? allocation.externalIp : allocation.ip}:${allocation.port}`
        : 'Sem alocação';

    const currentCpu = stats?.cpu ?? 0;
    const currentRamBytes = stats?.ram ?? 0;
    const currentDiskBytes = stats?.disk ?? 0;

    const formatUsage = (bytes: number) => {
        const mb = bytes / 1024 / 1024;
        return mb >= 1024 ? (mb / 1024).toFixed(2) + ' GB' : mb.toFixed(1) + ' MB';
    };

    const cpuPercent = server.cpu > 0 ? Math.min((currentCpu / server.cpu) * 100, 100) : (currentCpu > 100 ? 100 : currentCpu);
    const ramInMB = currentRamBytes / 1024 / 1024;
    const ramPercent = server.ram > 0 ? Math.min((ramInMB / server.ram) * 100, 100) : 0;
    const diskInMB = currentDiskBytes / 1024 / 1024;
    const diskPercent = server.disk > 0 ? Math.min((diskInMB / server.disk) * 100, 100) : 0;

    return (
        <Link
            href={`/server/${server.serverUuid.split('-')[0]}`}
            className="group flex flex-col xl:flex-row items-start xl:items-center justify-between p-3 gap-6 bg-[var(--color-secondary)] border border-white/5 rounded-xl hover:bg-white/[0.02] hover:border-white/10 transition-all duration-300"
        >
            {/* Esquerda: Ícone e Info do Servidor */}
            <div className="flex items-center gap-5 w-full xl:w-auto">
                <div className="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-[var(--color-text-sub)] group-hover:text-[var(--color-primary)] transition-colors shadow-sm shrink-0">
                    <svg width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                        <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                        <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                        <line x1="6" y1="6" x2="6.01" y2="6"></line>
                        <line x1="6" y1="18" x2="6.01" y2="18"></line>
                    </svg>
                </div>

                <div className="flex flex-col min-w-0">
                    <h3 className="text-[15px] font-bold text-[var(--color-text-value)] tracking-tight truncate max-w-[250px] md:max-w-[320px]">
                        {server.name}
                    </h3>

                    <span className="font-mono text-[13px] text-[var(--color-text-sub)] mt-0.5 truncate">
                        {address}
                    </span>

                    {/* Tag de Usuário APENAS se for Admin View */}
                    {isAdminView && server.user && (
                        <div className="mt-2 flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-black/20 border border-white/5 w-fit shadow-inner">
                            <svg width="12" height="12" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24" className="text-[var(--color-text-sub)]">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span className="text-[11px] font-mono text-[var(--color-text-sub)] truncate max-w-[200px]">
                                <span className="text-[var(--color-primary)] font-bold">#{server.user.id}</span> {server.user.first_name}
                            </span>
                        </div>
                    )}

                    {server.description && !isAdminView && (
                        <p className="mt-1 text-[12px] text-[var(--color-text-sub)] opacity-80 truncate max-w-[250px] md:max-w-[320px]">
                            {server.description}
                        </p>
                    )}
                </div>
            </div>

            {/* Direita: Status e Métricas Inset */}
            <div className="flex flex-col sm:flex-row items-start sm:items-center gap-6 w-full xl:w-auto">

                <div className="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/5 border border-white/5 shrink-0">
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

                <div className="flex flex-wrap sm:flex-nowrap items-center gap-5 sm:gap-8 bg-black/20 border border-white/5 rounded-lg px-6 py-4 shadow-inner w-full sm:w-auto">
                    {/* CPU */}
                    <div className="flex flex-col w-full sm:w-24">
                        <div className="flex items-end justify-between mb-2">
                            <span className="text-[10px] text-[var(--color-text-sub)] uppercase font-black tracking-widest">CPU</span>
                            <span className="text-[12px] font-mono text-[var(--color-text-value)] font-medium">
                                {isConnecting ? '--' : `${currentCpu.toFixed(1)}%`}
                            </span>
                        </div>
                        <div className="w-full h-1 bg-black/40 rounded-full overflow-hidden">
                            <div
                                className="h-full rounded-full transition-all duration-700 ease-out"
                                style={{ width: `${cpuPercent}%`, backgroundColor: cpuPercent > 85 ? 'var(--color-danger)' : cpuPercent > 60 ? 'var(--color-warning)' : 'var(--color-primary)' }}
                            />
                        </div>
                    </div>

                    {/* RAM */}
                    <div className="flex flex-col w-full sm:w-28">
                        <div className="flex items-end justify-between mb-2">
                            <span className="text-[10px] text-[var(--color-text-sub)] uppercase font-black tracking-widest">RAM</span>
                            <div className="flex items-baseline gap-1">
                                <span className="text-[12px] font-mono text-[var(--color-text-value)] font-medium">{isConnecting ? '--' : formatUsage(currentRamBytes)}</span>
                            </div>
                        </div>
                        <div className="w-full h-1 bg-black/40 rounded-full overflow-hidden">
                            <div
                                className="h-full rounded-full transition-all duration-700 ease-out"
                                style={{ width: `${ramPercent}%`, backgroundColor: ramPercent > 85 ? 'var(--color-danger)' : ramPercent > 60 ? 'var(--color-warning)' : 'var(--color-primary)' }}
                            />
                        </div>
                    </div>

                    {/* DISK (SSD) */}
                    <div className="flex flex-col w-full sm:w-28">
                        <div className="flex items-end justify-between mb-2">
                            <span className="text-[10px] text-[var(--color-text-sub)] uppercase font-black tracking-widest">SSD</span>
                            <div className="flex items-baseline gap-1">
                                <span className="text-[12px] font-mono text-[var(--color-text-value)] font-medium">{isConnecting ? '--' : formatUsage(currentDiskBytes)}</span>
                            </div>
                        </div>
                        <div className="w-full h-1 bg-black/40 rounded-full overflow-hidden">
                            <div
                                className="h-full rounded-full transition-all duration-700 ease-out"
                                style={{ width: `${diskPercent}%`, backgroundColor: diskPercent > 85 ? 'var(--color-danger)' : diskPercent > 60 ? 'var(--color-warning)' : 'var(--color-primary)' }}
                            />
                        </div>
                    </div>

                </div>
            </div>
        </Link>
    );
};

export default ServerRow;