import React, { useState, useEffect, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import ServerRow from './ServerRow';
import { ServerData } from '../../types';
import LoadingPage from "@/web/components/commons/LoadingPage";
import Footer from "@/web/components/commons/Footer";
import Checkbox from "@/web/components/commons/components/Checkbox";

interface ServerStats {
    cpu: number;
    ram: number;
    disk: number;
}

interface ServerContainerProps {
    isAdmin?: boolean;
}

const DashboardContainer: React.FC<ServerContainerProps> = ({ isAdmin = false }) => {
    // Inicializando states direto do localStorage para manter o cache do F5
    const [showOthers, setShowOthers] = useState(() => {
        if (typeof window !== 'undefined') {
            const saved = localStorage.getItem('hight_show_others');
            return saved ? JSON.parse(saved) : false;
        }
        return false;
    });

    const [activeFilter, setActiveFilter] = useState(() => {
        if (typeof window !== 'undefined') {
            return localStorage.getItem('hight_active_filter') || 'all';
        }
        return 'all';
    });

    const [servers, setServers] = useState<ServerData[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [serverStatuses, setServerStatuses] = useState<Record<number, string>>({});
    const [serverStats, setServerStats] = useState<Record<number, ServerStats>>({});

    // Salvando preferências no LocalStorage sempre que mudarem
    useEffect(() => {
        localStorage.setItem('hight_show_others', JSON.stringify(showOthers));
    }, [showOthers]);

    useEffect(() => {
        localStorage.setItem('hight_active_filter', activeFilter);
    }, [activeFilter]);

    useEffect(() => {
        const fetchServers = async () => {
            setIsLoading(true);
            try {
                const endpoint = (showOthers && isAdmin)
                    ? '/api/v1/users/servers?type=others'
                    : '/api/v1/users/servers';

                const response = await fetch(endpoint);
                const data = await response.json();

                const servers = data.servers;
                setServers(Array.isArray(servers) ? servers : []);
            } catch (error) {
                console.error("Erro ao buscar servidores:", error);
            } finally {
                setIsLoading(false);
            }
        };

        fetchServers();
    }, [showOthers, isAdmin]);

    useEffect(() => {
        if (servers.length === 0) return;

        const updateServerData = async () => {
            const newStatuses: Record<number, string> = {};
            const newStats: Record<number, ServerStats> = {};

            await Promise.all(servers.map(async (server) => {
                try {
                    const response = await fetch(`/api/v1/users/server/${server.id}/status`);

                    if (!response.ok) {
                        throw new Error('Erro na requisição');
                    }

                    const data = await response.json();
                    const { status, usage } = data.status;

                    newStatuses[server.id] = status;
                    newStats[server.id] = {
                        cpu: usage.cpu || 0,
                        ram: usage.memory || 0,
                        disk: usage.disk || 0
                    };
                } catch (error) {
                    console.error(`Erro ao buscar status do servidor ${server.id}:`, error);
                    newStatuses[server.id] = 'conectando';
                    newStats[server.id] = { cpu: 0, ram: 0, disk: 0 };
                }
            }));

            setServerStatuses(prev => ({ ...prev, ...newStatuses }));
            setServerStats(prev => ({ ...prev, ...newStats }));
        };

        updateServerData();
    }, [servers]);

    const groups = useMemo(() => {
        const uniqueGroups = new Set(servers.map(s => s.group || 'Geral'));
        return Array.from(uniqueGroups);
    }, [servers]);

    const groupedServers = useMemo(() => {
        const filtered = servers.filter(s => activeFilter === 'all' || (s.group || 'Geral') === activeFilter);

        return filtered.reduce((acc, server) => {
            const group = server.group || 'Geral';
            if (!acc[group]) acc[group] = [];
            acc[group].push(server);
            return acc;
        }, {} as Record<string, ServerData[]>);
    }, [servers, activeFilter]);

    return (
        // Retornado a largura para max-w-7xl padrão, resolvendo o problema das margens engolidas
        <div className="w-full max-w-7xl mx-auto mt-12 px-10 overflow-x-hidden animate-[fadeIn_0.4s_ease-out]">

            <div className="flex flex-col md:flex-row justify-between items-end mb-10 gap-6 w-full">

                {/* Filtros Livres e Limpos (Estilo Pills independentes) */}
                <div className="flex flex-col gap-3 w-full md:w-auto">
                    <h2 className="text-[11px] font-black text-[var(--color-text-sub)] uppercase tracking-widest pl-1 hidden md:block">Filtrar Categoria</h2>
                    <div className="flex gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-hide">
                        <button
                            onClick={() => setActiveFilter('all')}
                            className={`px-5 py-2.5 rounded-xl text-[13px] font-bold transition-all duration-300 border ${
                                activeFilter === 'all'
                                    ? 'bg-[var(--color-secondary)] text-[var(--color-text-value)] border-white/10 shadow-sm'
                                    : 'bg-transparent text-[var(--color-text-sub)] border-transparent hover:text-[var(--color-text-value)] hover:bg-white/5'
                            }`}
                        >
                            Todos
                        </button>
                        {groups.map(group => (
                            <button
                                key={group}
                                onClick={() => setActiveFilter(group)}
                                className={`px-5 py-2.5 rounded-xl text-[13px] font-bold transition-all duration-300 border ${
                                    activeFilter === group
                                        ? 'bg-[var(--color-secondary)] text-[var(--color-text-value)] border-white/10 shadow-sm'
                                        : 'bg-transparent text-[var(--color-text-sub)] border-transparent hover:text-[var(--color-text-value)] hover:bg-white/5'
                                }`}
                            >
                                {group}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Toggle Checkbox Modernizado (Sem fundo escuro pesado) */}
                {isAdmin && (
                    <div className="flex items-center gap-4 cursor-pointer transition-all duration-300 group whitespace-nowrap bg-(--color-secondary) border  border-white/5 px-5 py-3 rounded-xl mb-1 md:mb-0">
                        <span className="text-[12px] font-bold text-(--color-text-sub) transition-colors group-hover:text-(--color-text-value)">
                            {showOthers ? 'Exibindo outros servidores' : 'Exibindo seus servidores'}
                        </span>
                        <Checkbox
                            onChange={() => setShowOthers(!showOthers)}
                            checked={showOthers}
                        />
                    </div>
                )}
            </div>

            {/* Container da Lista */}
            <div className="flex flex-col min-h-[400px]">
                <AnimatePresence mode="wait">
                    {isLoading ? (
                        <div key="loading" className="py-20 flex items-center justify-center">
                            <LoadingPage />
                        </div>
                    ) : Object.keys(groupedServers).length > 0 ? (
                        <motion.div
                            key="server-list"
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -10 }}
                            transition={{ duration: 0.3 }}
                            className="flex flex-col gap-10"
                        >
                            {Object.entries(groupedServers).map(([group, groupServers]) => (
                                <div key={group} className="flex flex-col gap-6">
                                    {/* Cabeçalho da Categoria com linha esfumaçada */}
                                    <div className="flex items-center gap-4">
                                        <h3 className="text-[15px] font-bold text-[var(--color-text-value)] opacity-90 tracking-tight">{group}</h3>
                                        <div className="h-[1px] flex-1 bg-gradient-to-r from-white/10 to-transparent"></div>
                                    </div>

                                    <div className="grid gap-4">
                                        {groupServers.map(server => (
                                            <ServerRow
                                                key={server.id}
                                                server={server}
                                                status={serverStatuses[server.id] || 'conectando'}
                                                stats={serverStats[server.id]}
                                                allocation={server.allocation}
                                            />
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </motion.div>
                    ) : (
                        /* Empty State Limpo - Sem fundo afundado, usando design "Dashed" (Tracejado) */
                        <motion.div
                            key="no-servers"
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            className="flex flex-col items-center justify-center text-center mt-6 py-20 rounded-2xl border-2 border-dashed border-white/5"
                        >
                            <div className="w-16 h-16 rounded-full bg-[var(--color-secondary)] border border-white/5 flex items-center justify-center text-[var(--color-text-sub)] mb-5 shadow-sm">
                                <svg width="26" height="26" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M5 12h14M12 5l7 7-7 7" />
                                </svg>
                            </div>
                            <div>
                                <p className="text-[16px] font-bold text-[var(--color-text-value)] tracking-tight">Nenhum servidor encontrado</p>
                                <p className="text-[13px] font-medium text-[var(--color-text-sub)] mt-1.5 leading-relaxed">
                                    Altere os filtros ou crie uma nova instância.
                                </p>
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>
            </div>

            <Footer/>
        </div>
    );
};

export default DashboardContainer;