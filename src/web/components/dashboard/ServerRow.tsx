import React from 'react';
import { ServerData } from "@/web/types";
import { Link } from "vatts/react";

interface ServerRowProps {
    server: ServerData & { description?: string };
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
    conectando: { color: 'var(--color-warning)', label: 'Conectando...', isAnimated: true },
};

const ServerRow: React.FC<ServerRowProps> = ({ server, status = 'offline', stats, allocation }) => {
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

    const formatLimit = (mb: number) => {
        if (mb === 0) return '∞';
        return mb >= 1024 ? (mb / 1024).toFixed(0) + ' GB' : mb + ' MB';
    };

    // Cálculos para as barras de progresso
    const cpuPercent = server.cpu > 0 ? Math.min((currentCpu / server.cpu) * 100, 100) : (currentCpu > 100 ? 100 : currentCpu);
    const ramInMB = currentRamBytes / 1024 / 1024;
    const ramPercent = server.ram > 0 ? Math.min((ramInMB / server.ram) * 100, 100) : 0;
    const diskInMB = currentDiskBytes / 1024 / 1024;
    const diskPercent = server.disk > 0 ? Math.min((diskInMB / server.disk) * 100, 100) : 0;

    return (
        <Link
            href={`/server/${server.serverUuid.split('-')[0]}`}
            className="group relative flex flex-col md:flex-row items-start md:items-center justify-between p-5 rounded-xl bg-(--color-secondary) shadow-lg hover:shadow-[0_8px_30px_rgba(223,95,255,0.1)] transition-all duration-300 overflow-hidden"
        >
            {/* Faixa de cor lateral baseada no status */}
            <div
                className="absolute left-0 top-0 bottom-0 w-[3px] transition-colors duration-500"
                style={{ backgroundColor: currentStatus.color, opacity: currentStatus.isAnimated ? 0.8 : 0.4 }}
            />

            {/* Esquerda: Ícone e Info do Servidor */}
            <div className="flex items-center gap-5 w-full md:w-2/5 pl-2">
                <div className="w-14 h-14 rounded-lg flex items-center justify-center text-(--color-primary) bg-(--color-terciary) group-hover:scale-105 transition-transform shrink-0">
                    <svg width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" viewBox="0 0 24 24">
                        <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                        <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                        <line x1="6" y1="6" x2="6.01" y2="6"></line>
                        <line x1="6" y1="18" x2="6.01" y2="18"></line>
                    </svg>
                </div>

                <div className="flex flex-col min-w-0">
                    <div className="flex items-center gap-3">
                        <h3 className="text-(--color-text-label) font-bold text-lg tracking-tight group-hover:text-(--color-primary) transition-colors truncate max-w-[220px]">
                            {server.name}
                        </h3>

                        {/* Badge de Status Moderno */}
                        <div className="flex items-center gap-2 px-2.5 py-1 rounded-md shrink-0">
                            <span className="relative flex h-2 w-2">
                                {currentStatus.isAnimated && (
                                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style={{ backgroundColor: currentStatus.color }}></span>
                                )}
                                <span className="relative inline-flex rounded-full h-2 w-2" style={{ backgroundColor: currentStatus.color, boxShadow: `0 0 8px ${currentStatus.color}` }}></span>
                            </span>
                            <span className="text-[10px] uppercase font-bold tracking-widest" style={{ color: currentStatus.color }}>
                                {currentStatus.label}
                            </span>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 mt-1.5">
                        <span className="font-mono text-sm text-(--color-text-sub) group-hover:text-(--color-text-label) transition-colors">{address}</span>
                    </div>

                    {/* Descrição Adicionada Aqui */}
                    {server.description && (
                        <p className="mt-1 text-xs text-(--color-text-sub) opacity-75 truncate max-w-[250px] md:max-w-[320px] group-hover:opacity-100 transition-opacity duration-300">
                            {server.description}
                        </p>
                    )}
                </div>
            </div>

            {/* Direita: Métricas (CPU, RAM, DISK) */}
            <div className="flex items-center justify-end gap-8 flex-1 w-full md:w-auto mt-6 md:mt-0 pr-4">

                {/* CPU Block */}
                <div className="flex flex-col w-24">
                    <div className="flex items-center justify-between mb-1">
                        <span className="text-[10px] text-(--color-text-sub) uppercase font-bold tracking-widest">CPU</span>
                        <span className="text-xs font-mono text-text-(--color-text-label) font-medium">
                            {isConnecting ? '--' : `${currentCpu.toFixed(1)}%`}
                        </span>
                    </div>
                    {/* Progress Bar */}
                    <div className="w-full h-1.5 bg-black/30 rounded-full overflow-hidden">
                        <div
                            className="h-full rounded-full transition-all duration-700 ease-out"
                            style={{
                                width: `${cpuPercent}%`,
                                backgroundColor: cpuPercent > 85 ? 'var(--color-danger)' : cpuPercent > 60 ? 'var(--color-warning)' : 'var(--color-primary)'
                            }}
                        />
                    </div>
                </div>

                {/* RAM Block */}
                <div className="flex flex-col w-36">
                    <div className="flex items-center justify-between mb-1">
                        <span className="text-[10px] text-(--color-text-sub) uppercase font-bold tracking-widest">RAM</span>
                        <div className="flex items-baseline gap-1 whitespace-nowrap">
                            <span className="text-xs font-mono text-(--color-text-label) font-medium">{isConnecting ? '--' : formatUsage(currentRamBytes)}</span>
                            <span className="text-[10px] font-mono text-(--color-text-sub)">/ {formatLimit(server.ram)}</span>
                        </div>
                    </div>
                    {/* Progress Bar */}
                    <div className="w-full h-1.5 bg-black/30 rounded-full overflow-hidden">
                        <div
                            className="h-full rounded-full transition-all duration-700 ease-out"
                            style={{
                                width: `${ramPercent}%`,
                                backgroundColor: ramPercent > 85 ? 'var(--color-danger)' : ramPercent > 60 ? 'var(--color-warning)' : 'var(--color-primary)'
                            }}
                        />
                    </div>
                </div>

                {/* DISK Block */}
                <div className="flex flex-col w-36">
                    <div className="flex items-center justify-between mb-1">
                        <span className="text-[10px] text-(--color-text-sub) uppercase font-bold tracking-widest">SSD</span>
                        <div className="flex items-baseline gap-1 whitespace-nowrap">
                            <span className="text-xs font-mono text-(--color-text-label) font-medium">{isConnecting ? '--' : formatUsage(currentDiskBytes)}</span>
                            <span className="text-[10px] font-mono text-(--color-text-sub)">/ {formatLimit(server.disk)}</span>
                        </div>
                    </div>
                    {/* Progress Bar */}
                    <div className="w-full h-1.5 bg-black/30 rounded-full overflow-hidden">
                        <div
                            className="h-full rounded-full transition-all duration-700 ease-out"
                            style={{
                                width: `${diskPercent}%`,
                                backgroundColor: diskPercent > 85 ? 'var(--color-danger)' : diskPercent > 60 ? 'var(--color-warning)' : 'var(--color-primary)'
                            }}
                        />
                    </div>
                </div>

            </div>
        </Link>
    );
};

export default ServerRow;