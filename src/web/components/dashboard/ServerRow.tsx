import React from 'react';
import { ServerData } from "@/web/types";
import { Link } from "nytlex/react";

interface ServerRowProps {
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
    isAdminView?: boolean;
}

// Lógica de status 100% adaptada para as variáveis do root
const STATUS_CONFIG: Record<string, { color: string; label: string; isAnimated: boolean; gradient: string }> = {
    running: { color: 'var(--color-success)', label: 'Online', isAnimated: true, gradient: 'from-[var(--color-success)]/40 via-[var(--color-terciary)]/10' },
    initializing: { color: 'var(--color-warning)', label: 'Iniciando', isAnimated: true, gradient: 'from-[var(--color-warning)]/40 via-[var(--color-terciary)]/10' },
    starting: { color: 'var(--color-warning)', label: 'Iniciando', isAnimated: true, gradient: 'from-[var(--color-warning)]/40 via-[var(--color-terciary)]/10' },
    installing: { color: 'var(--color-warning)', label: 'Instalando', isAnimated: true, gradient: 'from-[var(--color-warning)]/40 via-[var(--color-terciary)]/10' },
    stopping: { color: 'var(--color-warning)', label: 'Desligando', isAnimated: true, gradient: 'from-[var(--color-warning)]/40 via-[var(--color-terciary)]/10' },
    stopped: { color: 'var(--color-danger)', label: 'Offline', isAnimated: false, gradient: 'from-[var(--color-danger)]/40 via-[var(--color-terciary)]/10' },
    offline: { color: 'var(--color-danger)', label: 'Offline', isAnimated: false, gradient: 'from-[var(--color-danger)]/40 via-[var(--color-terciary)]/10' },
    suspended: { color: 'var(--color-warning)', label: 'Suspenso', isAnimated: false, gradient: 'from-[var(--color-warning)]/40 via-[var(--color-terciary)]/10' },
    conectando: { color: 'var(--color-warning)', label: 'Conectando...', isAnimated: true, gradient: 'from-[var(--color-warning)]/40 via-[var(--color-terciary)]/10' },
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
        <div className="group relative p-[4px] rounded-2xl transition-all duration-500 block w-full shadow-2xl">

            {/* Fake Border Layer usando a lógica da máscara e do background dinâmico do status */}
            <div
                className={`absolute inset-0 rounded-2xl bg-gradient-to-br ${currentStatus.gradient} to-transparent group-hover:via-[var(--color-terciary)]/30 pointer-events-none transition-all duration-500`}
                style={{
                    padding: "4px",
                    WebkitMask: "linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0)",
                    WebkitMaskComposite: "xor",
                    maskComposite: "exclude"
                }}
            />

            <Link
                href={`/server/${server.serverUuid.split('-')[0]}`}
                className="relative block h-full bg-[var(--color-secondary)] backdrop-blur-xl rounded-[14px] p-3"
            >
                <div className="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-6">

                    <div className="flex items-center gap-5 w-full xl:w-auto">
                        <div className="w-14 h-14 rounded-xl bg-[var(--color-terciary)] text-[var(--color-text-label)] group-hover:text-[var(--color-text-value)] transition-colors shadow-xl shrink-0 flex items-center justify-center">
                            <svg width="24" height="24" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                                <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                                <line x1="6" y1="6" x2="6.01" y2="6"></line>
                                <line x1="6" y1="18" x2="6.01" y2="18"></line>
                            </svg>
                        </div>

                        <div className="flex flex-col min-w-0">
                            <h3 className="text-[18px] font-black text-[var(--color-text-value)] tracking-tight truncate max-w-[250px] md:max-w-[320px]">
                                {server.name}
                            </h3>

                            <span className="font-mono text-[13px] text-[var(--color-text-sub)] mt-1 truncate">
                                {address}
                            </span>

                            {isAdminView && server.user && (
                                <div className="mt-2.5 flex items-center gap-2 px-3 py-1.5 rounded-lg bg-[var(--color-background)]/50 w-fit shadow-lg">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24" className="text-[var(--color-text-sub)]">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    <span className="text-[12px] font-mono text-[var(--color-text-label)] truncate max-w-[200px]">
                                        <span className="text-[var(--color-text-value)] font-bold">#{server.user.id}</span> {server.user.first_name}
                                    </span>
                                </div>
                            )}

                            {server.description && !isAdminView && (
                                <p className="mt-2 text-[13px] text-[var(--color-text-sub)] opacity-80 truncate max-w-[250px] md:max-w-[320px]">
                                    {server.description}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="flex flex-col sm:flex-row items-start sm:items-center gap-6 w-full xl:w-auto">

                        <div className="flex items-center gap-3 px-4 py-2 rounded-xl bg-[var(--color-background)]/50 shadow-xl shrink-0">
                            <span className="relative flex h-2.5 w-2.5">
                                {currentStatus.isAnimated && (
                                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style={{ backgroundColor: currentStatus.color }}></span>
                                )}
                                <span className="relative inline-flex rounded-full h-2.5 w-2.5" style={{ backgroundColor: currentStatus.color, boxShadow: `0 0 10px ${currentStatus.color}` }}></span>
                            </span>
                            <span className="text-[11px] uppercase font-black tracking-widest text-[var(--color-text-value)]">
                                {currentStatus.label}
                            </span>
                        </div>

                        <div className="flex flex-wrap sm:flex-nowrap items-center gap-6 sm:gap-8 bg-[var(--color-background)]/50 rounded-xl px-6 py-4 shadow-xl w-full sm:w-auto">
                            {/* CPU */}
                            <div className="flex flex-col w-full sm:w-24">
                                <div className="flex items-end justify-between mb-2.5">
                                    <span className="text-[10px] text-[var(--color-text-label)] uppercase font-black tracking-widest">CPU</span>
                                    <span className="text-[13px] font-mono text-[var(--color-text-value)] font-bold">
                                        {isConnecting ? '--' : `${currentCpu.toFixed(1)}%`}
                                    </span>
                                </div>
                                <div className="w-full h-1.5 bg-[var(--color-terciary)] rounded-full overflow-hidden">
                                    <div
                                        className="h-full rounded-full transition-all duration-700 ease-out shadow-[0_0_10px_currentColor]"
                                        style={{
                                            width: `${cpuPercent}%`,
                                            backgroundColor: cpuPercent > 85 ? 'var(--color-danger)' : cpuPercent > 60 ? 'var(--color-warning)' : 'var(--color-primary)',
                                            color: cpuPercent > 85 ? 'var(--color-danger)' : cpuPercent > 60 ? 'var(--color-warning)' : 'var(--color-primary)'
                                        }}
                                    />
                                </div>
                            </div>

                            {/* RAM */}
                            <div className="flex flex-col w-full sm:w-28">
                                <div className="flex items-end justify-between mb-2.5">
                                    <span className="text-[10px] text-[var(--color-text-label)] uppercase font-black tracking-widest">RAM</span>
                                    <span className="text-[13px] font-mono text-[var(--color-text-value)] font-bold">{isConnecting ? '--' : formatUsage(currentRamBytes)}</span>
                                </div>
                                <div className="w-full h-1.5 bg-[var(--color-terciary)] rounded-full overflow-hidden">
                                    <div
                                        className="h-full rounded-full transition-all duration-700 ease-out shadow-[0_0_10px_currentColor]"
                                        style={{
                                            width: `${ramPercent}%`,
                                            backgroundColor: ramPercent > 85 ? 'var(--color-danger)' : ramPercent > 60 ? 'var(--color-warning)' : 'var(--color-primary)',
                                            color: ramPercent > 85 ? 'var(--color-danger)' : ramPercent > 60 ? 'var(--color-warning)' : 'var(--color-primary)'
                                        }}
                                    />
                                </div>
                            </div>

                            {/* DISK */}
                            <div className="flex flex-col w-full sm:w-28">
                                <div className="flex items-end justify-between mb-2.5">
                                    <span className="text-[10px] text-[var(--color-text-label)] uppercase font-black tracking-widest">SSD</span>
                                    <span className="text-[13px] font-mono text-[var(--color-text-value)] font-bold">{isConnecting ? '--' : formatUsage(currentDiskBytes)}</span>
                                </div>
                                <div className="w-full h-1.5 bg-[var(--color-terciary)] rounded-full overflow-hidden">
                                    <div
                                        className="h-full rounded-full transition-all duration-700 ease-out shadow-[0_0_10px_currentColor]"
                                        style={{
                                            width: `${diskPercent}%`,
                                            backgroundColor: diskPercent > 85 ? 'var(--color-danger)' : diskPercent > 60 ? 'var(--color-warning)' : 'var(--color-primary)',
                                            color: diskPercent > 85 ? 'var(--color-danger)' : diskPercent > 60 ? 'var(--color-warning)' : 'var(--color-primary)'
                                        }}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Link>
        </div>
    );
};

export default ServerRow;