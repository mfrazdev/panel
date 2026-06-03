import React, { useState, useEffect, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import ServerRow from './ServerRow';
import { ServerData } from '../../types';
import LoadingPage from "@/web/components/commons/LoadingPage";
import Footer from "@/web/components/commons/Footer";
import Checkbox from "@/web/components/commons/components/Checkbox";
import Select from "@/web/components/commons/components/Select";
import { useSession } from "@vatts/auth/react";

interface ServerStats {
    cpu: number;
    ram: number;
    disk: number;
}

const ITEMS_PER_PAGE = 10;

const statusOptions = [
    { label: 'Status: Todos', value: 'all' },
    { label: 'Status: Online', value: 'running' },
    { label: 'Status: Offline', value: 'offline' },
    { label: 'Status: Suspensos', value: 'suspended' },
];

const sortOptions = [
    { label: 'Ordem: Mais Recentes', value: 'newest' },
    { label: 'Ordem: Mais Antigos', value: 'oldest' },
    { label: 'Ordem: Alfabeto (A-Z)', value: 'name' },
    { label: 'Ordem: Maior RAM', value: 'ram_desc' },
];

const DashboardContainer: React.FC = () => {
    const session = useSession();

    const [showOthers, setShowOthers] = useState(() => {
        if (typeof window !== 'undefined') {
            const saved = localStorage.getItem('hight_show_others');
            return saved ? JSON.parse(saved) : false;
        }
        return false;
    });
    const [servers, setServers] = useState<ServerData[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [serverStatuses, setServerStatuses] = useState<Record<number, string>>({});
    const [serverStats, setServerStats] = useState<Record<number, ServerStats>>({});
    const [currentPage, setCurrentPage] = useState(1);

    const [activeFilter, setActiveFilter] = useState(() => {
        if (typeof window !== 'undefined') {
            return localStorage.getItem('hight_active_filter') || 'all';
        }
        return 'all';
    });

    const [searchQuery, setSearchQuery] = useState('');
    const [adminStatusFilter, setAdminStatusFilter] = useState('all');
    const [adminSortBy, setAdminSortBy] = useState('newest');

    useEffect(() => {
        localStorage.setItem('hight_show_others', JSON.stringify(showOthers));
        setSearchQuery('');
        setAdminStatusFilter('all');
        setAdminSortBy('newest');
        setCurrentPage(1);
    }, [showOthers]);

    useEffect(() => {
        localStorage.setItem('hight_active_filter', activeFilter);
        setCurrentPage(1);
    }, [activeFilter]);

    useEffect(() => {
        setCurrentPage(1);
    }, [searchQuery, adminStatusFilter, adminSortBy]);

    useEffect(() => {
        const fetchServers = async () => {
            setIsLoading(true);
            try {
                const endpoint = (showOthers && session.data?.user.role === 'admin')
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
    }, [showOthers, session.data?.user.role]);

    useEffect(() => {
        if (servers.length === 0) return;

        const updateServerData = async () => {
            const newStatuses: Record<number, string> = {};
            const newStats: Record<number, ServerStats> = {};

            await Promise.all(servers.map(async (server) => {
                if (server.suspended === 1) {
                    newStatuses[server.id] = 'suspended';
                    newStats[server.id] = { cpu: 0, ram: 0, disk: 0 };
                    return;
                }

                try {
                    const response = await fetch(`/api/v1/users/server/${server.id}/status`);
                    if (!response.ok) throw new Error('Erro na requisição');

                    const data = await response.json();
                    const { status, usage } = data.status;

                    newStatuses[server.id] = status;
                    newStats[server.id] = {
                        cpu: usage.cpu || 0,
                        ram: usage.memory || 0,
                        disk: usage.disk || 0
                    };
                } catch (error) {
                    newStatuses[server.id] = 'offline';
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

    const groupedNormalServers = useMemo(() => {
        if (showOthers) return {};
        return servers
            .filter(s => activeFilter === 'all' || (s.group || 'Geral') === activeFilter)
            .reduce((acc, server) => {
                const groupName = server.group || 'Geral';
                if (!acc[groupName]) acc[groupName] = [];
                acc[groupName].push(server);
                return acc;
            }, {} as Record<string, ServerData[]>);
    }, [servers, activeFilter, showOthers]);

    const adminMetrics = useMemo(() => {
        const total = servers.length;
        const suspended = servers.filter(s => s.suspended === 1).length;
        const online = servers.filter(s => serverStatuses[s.id] === 'running').length;
        const totalRamMB = servers.reduce((acc, s) => acc + (s.ram || 0), 0);
        const totalRam = totalRamMB >= 1024 ? `${(totalRamMB / 1024).toFixed(1)} GB` : `${totalRamMB} MB`;

        return { total, suspended, online, totalRam };
    }, [servers, serverStatuses]);

    const adminFilteredServers = useMemo(() => {
        if (!showOthers) return [];
        let filtered = [...servers];

        if (searchQuery.trim() !== '') {
            const q = searchQuery.toLowerCase();
            filtered = filtered.filter((s: any) => {
                const userFirst = s.user?.first_name?.toLowerCase() || '';
                const userEmail = s.user?.email?.toLowerCase() || '';
                return s.name.toLowerCase().includes(q) ||
                    s.id.toString().includes(q) ||
                    userFirst.includes(q) ||
                    userEmail.includes(q);
            });
        }

        if (adminStatusFilter !== 'all') {
            filtered = filtered.filter(s => {
                if (adminStatusFilter === 'suspended') return s.suspended === 1;
                const status = serverStatuses[s.id] || 'offline';
                if (adminStatusFilter === 'running') return status === 'running';
                if (adminStatusFilter === 'offline') return status === 'offline' || status === 'stopped' || status === 'conectando';
                return true;
            });
        }

        filtered.sort((a, b) => {
            if (adminSortBy === 'newest') return b.id - a.id;
            if (adminSortBy === 'oldest') return a.id - b.id;
            if (adminSortBy === 'name') return a.name.localeCompare(b.name);
            if (adminSortBy === 'ram_desc') return b.ram - a.ram;
            return 0;
        });

        return filtered;
    }, [servers, showOthers, searchQuery, adminStatusFilter, adminSortBy, serverStatuses]);

    const totalPages = Math.ceil((showOthers ? adminFilteredServers.length : 0) / ITEMS_PER_PAGE);
    const paginatedAdminServers = adminFilteredServers.slice((currentPage - 1) * ITEMS_PER_PAGE, currentPage * ITEMS_PER_PAGE);

    return (
        <div className="w-full max-w-7xl mx-auto mt-12 px-14 overflow-x-hidden animate-[fadeIn_0.4s_ease-out] min-h-screen text-[var(--color-text-label)]">
            <div className="flex flex-col mb-10 gap-6 w-full">
                <div className="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 w-full">
                    {!showOthers && (
                        <div className="flex flex-col gap-3 w-full md:w-auto flex-1">
                            <h2 className="text-[11px] font-black text-[var(--color-text-sub)] uppercase tracking-widest pl-1 hidden md:block">Filtrar Categoria</h2>
                            <div className="flex gap-2 overflow-x-auto pb-2 md:pb-0 custom-scrollbar">
                                <button
                                    onClick={() => setActiveFilter('all')}
                                    className={`px-5 py-2.5 rounded-xl text-[13px] font-bold transition-all duration-300 ${
                                        activeFilter === 'all'
                                            ? 'bg-[var(--color-terciary)] text-[var(--color-primary)] shadow-md shadow-[var(--color-primary)]/10'
                                            : 'bg-transparent text-[var(--color-text-label)] hover:text-[var(--color-text-value)] hover:bg-[var(--color-secondary)]'
                                    }`}
                                >
                                    Todos
                                </button>
                                {groups.map(group => (
                                    <button
                                        key={group}
                                        onClick={() => setActiveFilter(group)}
                                        className={`px-5 py-2.5 rounded-xl text-[13px] font-bold transition-all duration-300 ${
                                            activeFilter === group
                                                ? 'bg-[var(--color-terciary)] text-[var(--color-primary)] shadow-md shadow-[var(--color-primary)]/10'
                                                : 'bg-transparent text-[var(--color-text-label)] hover:text-[var(--color-text-value)] hover:bg-[var(--color-secondary)]'
                                        }`}
                                    >
                                        {group}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {showOthers && <div className="flex-1" />}

                    {session.data?.user.role === 'admin' && (
                        <div className="flex items-center gap-4">
                            <span className="text-[12px] font-bold text-[var(--color-text-sub)] transition-colors group-hover:text-[var(--color-text-value)]">
                                {showOthers ? 'Exibindo outros servidores' : 'Exibindo seus servidores'}
                            </span>
                            <Checkbox
                                onChange={() => setShowOthers(!showOthers)}
                                checked={showOthers}
                            />
                        </div>
                    )}
                </div>

                {showOthers && (
                    <motion.div
                        initial={{ opacity: 0, height: 0 }}
                        animate={{ opacity: 1, height: 'auto' }}
                        className="flex flex-col gap-6 w-full pt-4"
                    >
                        <div className="grid grid-cols-2 lg:grid-cols-4 gap-6 w-full">
                            {/* Card Global */}
                            <div className="group relative p-[4px] rounded-2xl bg-gradient-to-br from-[var(--color-terciary)] via-[var(--color-secondary)] to-transparent hover:from-[var(--color-primary)] transition-all duration-500 shadow-xl h-full flex flex-col">
                                <div className="relative h-full bg-[var(--color-secondary)] backdrop-blur-xl rounded-[15px] p-5 flex items-center gap-4 transition-colors duration-500 ease-out">
                                    <div className="w-12 h-12 rounded-xl bg-[var(--color-terciary)] text-[var(--color-text-label)] group-hover:text-[var(--color-text-value)] flex items-center justify-center shrink-0 shadow-md transition-colors duration-300">
                                        <svg className="scale-90 group-hover:scale-105 transition-transform duration-300 ease-out" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect></svg>
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="text-[10px] font-black text-[var(--color-text-label)] uppercase tracking-widest mb-1 transition-colors duration-300">Globais</span>
                                        <span className="text-2xl font-black text-[var(--color-text-value)] leading-none transition-colors duration-300">{adminMetrics.total}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Card Online */}
                            <div className="group relative p-[4px] rounded-2xl bg-gradient-to-br from-[var(--color-success)]/40 via-[var(--color-secondary)] to-transparent hover:from-[var(--color-success)]/80 transition-all duration-500 shadow-xl shadow-[var(--color-success)]/5 h-full flex flex-col">
                                <div className="relative h-full bg-[var(--color-secondary)] backdrop-blur-xl rounded-[15px] p-5 flex items-center gap-4 overflow-hidden transition-colors duration-500 ease-out">
                                    <div className="w-12 h-12 rounded-xl bg-[var(--color-success)]/10 text-[var(--color-success)] flex items-center justify-center shrink-0 shadow-md">
                                        <svg className="scale-90 group-hover:scale-105 transition-transform duration-300 ease-out" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="text-[10px] font-black text-[var(--color-text-label)] uppercase tracking-widest mb-1 transition-colors duration-300">Online</span>
                                        <span className="text-2xl font-black text-[var(--color-text-value)] leading-none transition-colors duration-300">{adminMetrics.online}</span>
                                    </div>
                                    <div className="absolute inset-0 bg-gradient-to-br from-[var(--color-success)]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-[15px] pointer-events-none"></div>
                                </div>
                            </div>

                            {/* Card Suspensos */}
                            <div className="group relative p-[4px] rounded-2xl bg-gradient-to-br from-[var(--color-warning)]/40 via-[var(--color-secondary)] to-transparent hover:from-[var(--color-warning)]/80 transition-all duration-500 shadow-xl shadow-[var(--color-warning)]/5 h-full flex flex-col">
                                <div className="relative h-full bg-[var(--color-secondary)] backdrop-blur-xl rounded-[15px] p-5 flex items-center gap-4 overflow-hidden transition-colors duration-500 ease-out">
                                    <div className="w-12 h-12 rounded-xl bg-[var(--color-warning)]/10 text-[var(--color-warning)] flex items-center justify-center shrink-0 shadow-md">
                                        <svg className="scale-90 group-hover:scale-105 transition-transform duration-300 ease-out" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="text-[10px] font-black text-[var(--color-text-label)] uppercase tracking-widest mb-1 transition-colors duration-300">Suspensos</span>
                                        <span className="text-2xl font-black text-[var(--color-text-value)] leading-none transition-colors duration-300">{adminMetrics.suspended}</span>
                                    </div>
                                    <div className="absolute inset-0 bg-gradient-to-br from-[var(--color-warning)]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-[15px] pointer-events-none"></div>
                                </div>
                            </div>

                            {/* Card RAM */}
                            <div className="group relative p-[4px] rounded-2xl bg-gradient-to-br from-[var(--color-info)]/40 via-[var(--color-secondary)] to-transparent hover:from-[var(--color-info)]/80 transition-all duration-500 shadow-xl shadow-[var(--color-info)]/5 h-full flex flex-col">
                                <div className="relative h-full bg-[var(--color-secondary)] backdrop-blur-xl rounded-[15px] p-5 flex items-center gap-4 overflow-hidden transition-colors duration-500 ease-out">
                                    <div className="w-12 h-12 rounded-xl bg-[var(--color-info)]/10 text-[var(--color-info)] flex items-center justify-center shrink-0 shadow-md">
                                        <svg className="scale-90 group-hover:scale-105 transition-transform duration-300 ease-out" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg>
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="text-[10px] font-black text-[var(--color-text-label)] uppercase tracking-widest mb-1 transition-colors duration-300">RAM Alocada</span>
                                        <span className="text-2xl font-black text-[var(--color-text-value)] leading-none transition-colors duration-300">{adminMetrics.totalRam}</span>
                                    </div>
                                    <div className="absolute inset-0 bg-gradient-to-br from-[var(--color-info)]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-[15px] pointer-events-none"></div>
                                </div>
                            </div>
                        </div>

                        {/* Input de Pesquisa e Filtros (Usando Select já no padrão novo) */}
                        <div className="flex flex-col lg:flex-row gap-4 w-full items-center mt-2">
                            <div className="relative flex-1 w-full p-[4px] rounded-2xl bg-gradient-to-br from-[var(--color-terciary)] via-[var(--color-secondary)] to-transparent focus-within:from-[var(--color-primary)] focus-within:via-[var(--color-primary)]/50 transition-all duration-500 ease-out shadow-sm focus-within:shadow-[var(--color-primary)]/10">
                                <div className="relative bg-[var(--color-console)] backdrop-blur-xl rounded-[14px]">
                                    <svg className="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--color-text-sub)] w-4 h-4 transition-colors duration-300" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <input
                                        type="text"
                                        placeholder="Buscar por Nome, ID, Dono ou Email..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        className="w-full bg-transparent py-4 pl-11 pr-4 text-[13px] font-medium text-[var(--color-text-value)] placeholder:text-[var(--color-text-sub)]/50 focus:outline-none transition-all"
                                    />
                                </div>
                            </div>

                            <div className="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                                <div className="flex-1 sm:w-48 z-20">
                                    <Select
                                        options={statusOptions}
                                        value={adminStatusFilter}
                                        onChange={setAdminStatusFilter}
                                    />
                                </div>
                                <div className="flex-1 sm:w-48 z-10">
                                    <Select
                                        options={sortOptions}
                                        value={adminSortBy}
                                        onChange={setAdminSortBy}
                                    />
                                </div>
                            </div>
                        </div>
                    </motion.div>
                )}
            </div>

            <div className="flex flex-col min-h-[400px]">
                <AnimatePresence mode="wait">
                    {isLoading ? (
                        <div key="loading" className="py-20 flex items-center justify-center">
                            <LoadingPage />
                        </div>
                    ) : (showOthers ? adminFilteredServers.length > 0 : Object.keys(groupedNormalServers).length > 0) ? (
                        <motion.div
                            key="server-list"
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -10 }}
                            transition={{ duration: 0.3 }}
                            className="flex flex-col gap-10"
                        >
                            {showOthers ? (
                                <div className="flex flex-col gap-6">
                                    <div className="grid gap-5">
                                        {paginatedAdminServers.map(server => (
                                            <ServerRow
                                                key={server.id}
                                                server={server}
                                                status={serverStatuses[server.id] || 'conectando'}
                                                stats={serverStats[server.id]}
                                                allocation={server.allocation}
                                                isAdminView={true}
                                            />
                                        ))}
                                    </div>

                                    {totalPages > 1 && (
                                        <div className="flex items-center justify-center gap-4 mt-8 pt-4">
                                            <div className="p-[2px] rounded-2xl bg-gradient-to-br from-[var(--color-terciary)] to-transparent hover:from-[var(--color-primary)] transition-all duration-500">
                                                <button
                                                    onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
                                                    disabled={currentPage === 1}
                                                    className="px-6 py-3 rounded-[14px] bg-[var(--color-secondary)] text-[13px] font-bold text-[var(--color-text-value)] disabled:opacity-40 disabled:bg-transparent transition-colors shadow-sm"
                                                >
                                                    Anterior
                                                </button>
                                            </div>
                                            <span className="text-[13px] font-medium text-[var(--color-text-sub)]">
                                                Página <span className="text-[var(--color-text-value)] font-bold">{currentPage}</span> de {totalPages}
                                            </span>
                                            <div className="p-[2px] rounded-2xl bg-gradient-to-br from-[var(--color-terciary)] to-transparent hover:from-[var(--color-primary)] transition-all duration-500">
                                                <button
                                                    onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))}
                                                    disabled={currentPage === totalPages}
                                                    className="px-6 py-3 rounded-[14px] bg-[var(--color-secondary)] text-[13px] font-bold text-[var(--color-text-value)] disabled:opacity-40 disabled:bg-transparent transition-colors shadow-sm"
                                                >
                                                    Próxima
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                Object.entries(groupedNormalServers).map(([group, groupServers]) => (
                                    <div key={group} className="flex flex-col gap-6">
                                        <div className="flex items-center gap-4">
                                            <h3 className="text-[15px] font-black text-[var(--color-text-value)] tracking-widest uppercase">{group}</h3>
                                            <div className="h-[2px] flex-1 bg-gradient-to-r from-[var(--color-terciary)] to-transparent rounded-full opacity-50"></div>
                                        </div>

                                        <div className="grid gap-5">
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
                                ))
                            )}
                        </motion.div>
                    ) : (
                        <motion.div
                            key="no-servers"
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            className="flex flex-col items-center justify-center text-center mt-6 py-20 rounded-[16px] bg-[var(--color-secondary)]/30 border border-[var(--color-terciary)] shadow-lg"
                        >
                            <div className="w-16 h-16 rounded-2xl bg-[var(--color-terciary)] flex items-center justify-center text-[var(--color-text-sub)] mb-5 shadow-inner">
                                <svg width="26" height="26" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M5 12h14M12 5l7 7-7 7" />
                                </svg>
                            </div>
                            <div>
                                <p className="text-[18px] font-black text-[var(--color-text-value)] tracking-tight">Nenhum servidor encontrado</p>
                                <p className="text-[14px] font-medium text-[var(--color-text-sub)] mt-2 leading-relaxed">
                                    {(showOthers ? searchQuery || adminStatusFilter !== 'all' : false)
                                        ? 'Tente buscar com outros filtros ou termos.'
                                        : 'Altere os filtros ou crie uma nova instância.'}
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