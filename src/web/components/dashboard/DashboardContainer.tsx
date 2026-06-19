import React, { useState, useEffect, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import ServerRow from './ServerRow';
import { ServerData } from '../../types';
import LoadingPage from "@/web/components/commons/LoadingPage";
import Footer from "@/web/components/commons/Footer";
import Checkbox from "@/web/components/commons/components/Checkbox";
import Select from "@/web/components/commons/components/Select";
import { useSession } from "@nytlex/auth/react";

interface ServerStats {
    cpu: number;
    ram: number;
    disk: number;
}

const ITEMS_PER_PAGE = 15;

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

    // --- PERSISTÊNCIA DE ESTADOS (LocalStorage) ---
    const saveState = (key: string, value: any) => {
        if (typeof window !== 'undefined') localStorage.setItem(key, JSON.stringify(value));
    };

    const loadState = (key: string, defaultValue: any) => {
        if (typeof window !== 'undefined') {
            const saved = localStorage.getItem(key);
            return saved ? JSON.parse(saved) : defaultValue;
        }
        return defaultValue;
    };

    // Estados de Visão e Filtros Usuário
    const [showOthers, setShowOthers] = useState(() => loadState('hight_show_others', false));
    const [activeFilter, setActiveFilter] = useState(() => loadState('hight_active_filter', 'all'));
    const [userSearchQuery, setUserSearchQuery] = useState('');
    const [userStatusFilter, setUserStatusFilter] = useState('all');

    // Estados de Filtros Admin (Agora Persistentes)
    const [searchQuery, setSearchQuery] = useState(() => loadState('admin_search_query', ''));
    const [adminStatusFilter, setAdminStatusFilter] = useState(() => loadState('admin_status_filter', 'all'));
    const [adminSortBy, setAdminSortBy] = useState(() => loadState('admin_sort_by', 'newest'));
    const [adminGroupFilter, setAdminGroupFilter] = useState(() => loadState('admin_group_filter', 'all'));

    const [servers, setServers] = useState<ServerData[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [serverStatuses, setServerStatuses] = useState<Record<number, string>>({});
    const [serverStats, setServerStats] = useState<Record<number, ServerStats>>({});
    const [currentPage, setCurrentPage] = useState(1);

    // Sincronização de LocalStorage
    useEffect(() => { saveState('hight_show_others', showOthers); }, [showOthers]);
    useEffect(() => { saveState('hight_active_filter', activeFilter); }, [activeFilter]);
    useEffect(() => { saveState('admin_search_query', searchQuery); }, [searchQuery]);
    useEffect(() => { saveState('admin_status_filter', adminStatusFilter); }, [adminStatusFilter]);
    useEffect(() => { saveState('admin_sort_by', adminSortBy); }, [adminSortBy]);
    useEffect(() => { saveState('admin_group_filter', adminGroupFilter); }, [adminGroupFilter]);

    // Reset de página ao filtrar
    useEffect(() => {
        setCurrentPage(1);
    }, [searchQuery, adminStatusFilter, adminSortBy, adminGroupFilter, userSearchQuery, userStatusFilter, activeFilter]);

    // Busca de Servidores
    useEffect(() => {
        const fetchServers = async () => {
            setIsLoading(true);
            try {
                const endpoint = (showOthers && session.data?.user.role === 'admin')
                    ? '/api/v1/users/servers?type=others'
                    : '/api/v1/users/servers';

                const response = await fetch(endpoint);
                const data = await response.json();
                setServers(Array.isArray(data.servers) ? data.servers : []);
            } catch (error) {
                console.error("Erro ao buscar servidores:", error);
            } finally {
                setIsLoading(false);
            }
        };
        fetchServers();
    }, [showOthers, session.data?.user.role]);

    // Atualização de status em tempo real
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
                    if (!response.ok) throw new Error('Erro');
                    const data = await response.json();
                    const { status, usage } = data.status;
                    newStatuses[server.id] = status;
                    newStats[server.id] = { cpu: usage.cpu || 0, ram: usage.memory || 0, disk: usage.disk || 0 };
                } catch {
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

    // Métricas Usuário
    const userMetrics = useMemo(() => {
        if (showOthers) return { total: 0, online: 0, ram: '0 MB' };
        const total = servers.length;
        const online = servers.filter(s => serverStatuses[s.id] === 'running').length;
        const totalRamMB = servers.reduce((acc, s) => acc + (s.ram || 0), 0);
        const ram = totalRamMB >= 1024 ? `${(totalRamMB / 1024).toFixed(1)} GB` : `${totalRamMB} MB`;
        return { total, online, ram };
    }, [servers, serverStatuses, showOthers]);

    // Filtro Usuário
    const groupedNormalServers = useMemo(() => {
        if (showOthers) return {};
        let filtered = [...servers];

        if (userSearchQuery.trim() !== '') {
            const q = userSearchQuery.toLowerCase();
            filtered = filtered.filter(s => s.name.toLowerCase().includes(q) || s.id.toString().includes(q));
        }

        if (userStatusFilter !== 'all') {
            filtered = filtered.filter(s => {
                if (userStatusFilter === 'suspended') return s.suspended === 1;
                const status = serverStatuses[s.id] || 'offline';
                if (userStatusFilter === 'running') return status === 'running' && s.suspended !== 1;
                if (userStatusFilter === 'offline') return (status === 'offline' || status === 'stopped') && s.suspended !== 1;
                return true;
            });
        }

        return filtered
            .filter(s => activeFilter === 'all' || (s.group || 'Geral') === activeFilter)
            .reduce((acc, server) => {
                const groupName = server.group || 'Geral';
                if (!acc[groupName]) acc[groupName] = [];
                acc[groupName].push(server);
                return acc;
            }, {} as Record<string, ServerData[]>);
    }, [servers, activeFilter, showOthers, userSearchQuery, userStatusFilter, serverStatuses]);

    // Métricas Admin
    const adminMetrics = useMemo(() => {
        const total = servers.length;
        const suspended = servers.filter(s => s.suspended === 1).length;
        const online = servers.filter(s => serverStatuses[s.id] === 'running').length;
        const totalRamMB = servers.reduce((acc, s) => acc + (s.ram || 0), 0);
        const totalRam = totalRamMB >= 1024 ? `${(totalRamMB / 1024).toFixed(1)} GB` : `${totalRamMB} MB`;
        return { total, suspended, online, totalRam };
    }, [servers, serverStatuses]);

    // --- FILTRAGEM SURREAL ADMIN ---
    const adminFilteredServers = useMemo(() => {
        if (!showOthers) return [];
        let filtered = [...servers];

        // Busca Ultra: Nome, ID, User, Email, Grupo
        if (searchQuery.trim() !== '') {
            const q = searchQuery.toLowerCase();
            filtered = filtered.filter((s: any) => {
                const userFirst = s.user?.first_name?.toLowerCase() || '';
                const userEmail = s.user?.email?.toLowerCase() || '';
                const groupName = (s.group || 'Geral').toLowerCase();
                return s.name.toLowerCase().includes(q) ||
                    s.id.toString().includes(q) ||
                    userFirst.includes(q) ||
                    userEmail.includes(q) ||
                    groupName.includes(q);
            });
        }

        // Filtro Status Admin
        if (adminStatusFilter !== 'all') {
            filtered = filtered.filter(s => {
                if (adminStatusFilter === 'suspended') return s.suspended === 1;
                const status = serverStatuses[s.id] || 'offline';
                if (adminStatusFilter === 'running') return status === 'running';
                if (adminStatusFilter === 'offline') return status === 'offline' || status === 'stopped' || status === 'conectando';
                return true;
            });
        }

        // Filtro de Grupo Admin
        if (adminGroupFilter !== 'all') {
            filtered = filtered.filter(s => (s.group || 'Geral') === adminGroupFilter);
        }

        // Ordenação
        filtered.sort((a, b) => {
            if (adminSortBy === 'newest') return b.id - a.id;
            if (adminSortBy === 'oldest') return a.id - b.id;
            if (adminSortBy === 'name') return a.name.localeCompare(b.name);
            if (adminSortBy === 'ram_desc') return b.ram - a.ram;
            return 0;
        });

        return filtered;
    }, [servers, showOthers, searchQuery, adminStatusFilter, adminSortBy, adminGroupFilter, serverStatuses]);

    const totalPages = Math.ceil((showOthers ? adminFilteredServers.length : 0) / ITEMS_PER_PAGE);
    const paginatedAdminServers = adminFilteredServers.slice((currentPage - 1) * ITEMS_PER_PAGE, currentPage * ITEMS_PER_PAGE);

    return (
        <div className="w-full max-w-7xl mx-auto mt-12 px-14 overflow-x-hidden animate-[fadeIn_0.4s_ease-out] min-h-screen text-[var(--color-text-label)]">
            <div className="flex flex-col mb-10 gap-6 w-full">

                {/* Header Superior */}
                <div className="flex justify-between items-center w-full pb-4 border-b border-[var(--color-terciary)]/30">
                    <div className="flex flex-col">
                        <h1 className="text-xl font-black text-[var(--color-text-value)] tracking-tight">Dashboard</h1>
                        <p className="text-[13px] font-medium text-[var(--color-text-sub)]">Gerencie suas instâncias de alto desempenho</p>
                    </div>
                    {session.data?.user.role === 'admin' && (
                        <div className="flex items-center gap-4 bg-[var(--color-secondary)]/40 px-4 py-2.5 rounded-xl border border-[var(--color-terciary)]/20">
                            <span className="text-[12px] font-bold text-[var(--color-text-sub)]">
                                {showOthers ? 'Mostrando outros servidores' : 'Mostrando seus servidores'}
                            </span>
                            <Checkbox
                                onChange={() => setShowOthers(!showOthers)}
                                checked={showOthers}
                            />
                        </div>
                    )}
                </div>

                {!showOthers && (
                    <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} className="flex flex-col gap-6 w-full">
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 w-full">
                            <div className="p-[3px] rounded-2xl bg-gradient-to-br from-[var(--color-terciary)] to-transparent shadow-md flex flex-col">
                                <div className="bg-[var(--color-secondary)] rounded-[14px] p-4 flex items-center gap-4">
                                    <div className="w-10 h-10 rounded-xl bg-[var(--color-terciary)] text-[var(--color-text-value)] flex items-center justify-center shrink-0">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect></svg>
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="text-[10px] font-black text-[var(--color-text-sub)] uppercase tracking-widest">Suas Instâncias</span>
                                        <span className="text-xl font-black text-[var(--color-text-value)] leading-none mt-0.5">{userMetrics.total}</span>
                                    </div>
                                </div>
                            </div>
                            <div className="p-[3px] rounded-2xl bg-gradient-to-br from-[var(--color-success)]/30 to-transparent shadow-md flex flex-col">
                                <div className="bg-[var(--color-secondary)] rounded-[14px] p-4 flex items-center gap-4">
                                    <div className="w-10 h-10 rounded-xl bg-[var(--color-success)]/10 text-[var(--color-success)] flex items-center justify-center shrink-0">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="text-[10px] font-black text-[var(--color-text-sub)] uppercase tracking-widest">Executando</span>
                                        <span className="text-xl font-black text-[var(--color-text-value)] leading-none mt-0.5">{userMetrics.online}</span>
                                    </div>
                                </div>
                            </div>
                            <div className="p-[3px] rounded-2xl bg-gradient-to-br from-[var(--color-info)]/30 to-transparent shadow-md flex flex-col">
                                <div className="bg-[var(--color-secondary)] rounded-[14px] p-4 flex items-center gap-4">
                                    <div className="w-10 h-10 rounded-xl bg-[var(--color-info)]/10 text-[var(--color-info)] flex items-center justify-center shrink-0">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect></svg>
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="text-[10px] font-black text-[var(--color-text-sub)] uppercase tracking-widest">RAM Utilizada</span>
                                        <span className="text-xl font-black text-[var(--color-text-value)] leading-none mt-0.5">{userMetrics.ram}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-col lg:flex-row gap-4 w-full items-center bg-[var(--color-secondary)]/20 p-3 rounded-2xl border border-[var(--color-terciary)]/10">
                            <div className="relative flex-1 w-full p-[2px] rounded-xl bg-gradient-to-br from-[var(--color-terciary)] to-transparent focus-within:from-[var(--color-primary)] transition-all duration-300">
                                <div className="relative bg-[var(--color-console)] rounded-[11px]">
                                    <svg className="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--color-text-sub)] w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <input
                                        type="text"
                                        placeholder="Buscar meu servidor por nome ou ID..."
                                        value={userSearchQuery}
                                        onChange={(e) => setUserSearchQuery(e.target.value)}
                                        className="w-full bg-transparent py-3 pl-11 pr-4 text-[13px] font-medium text-[var(--color-text-value)] placeholder:text-[var(--color-text-sub)]/40 focus:outline-none"
                                    />
                                </div>
                            </div>
                            <div className="flex flex-col sm:flex-row gap-3 w-full lg:w-auto shrink-0">
                                <div className="w-full sm:w-44 cursor-pointer">
                                    <Select
                                        options={[
                                            { label: 'Status: Todos', value: 'all' },
                                            { label: 'Status: Ligados', value: 'running' },
                                            { label: 'Status: Desligados', value: 'offline' },
                                            { label: 'Status: Suspensos', value: 'suspended' }
                                        ]}
                                        value={userStatusFilter}
                                        onChange={setUserStatusFilter}
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-col gap-2.5 w-full mt-1">
                            <span className="text-[10px] font-black text-[var(--color-text-sub)] uppercase tracking-widest pl-1">Filtrar por Categoria</span>
                            <div className="p-[2px] rounded-xl bg-gradient-to-r from-[var(--color-terciary)]/40 to-transparent">
                                <div className="flex gap-1 bg-[var(--color-secondary)]/60 p-1.5 rounded-[10px] overflow-x-auto custom-scrollbar">
                                    <button
                                        onClick={() => setActiveFilter('all')}
                                        className={`px-5 py-2 rounded-lg text-[13px] font-bold transition-all duration-200 cursor-pointer whitespace-nowrap ${
                                            activeFilter === 'all' ? 'bg-[var(--color-terciary)] text-[var(--color-text-value)] shadow-sm' : 'bg-transparent text-[var(--color-text-label)] hover:text-[var(--color-text-value)] hover:bg-[var(--color-secondary)]/50'
                                        }`}
                                    >
                                        Todos os Grupos
                                    </button>
                                    {groups.map(group => (
                                        <button
                                            key={group}
                                            onClick={() => setActiveFilter(group)}
                                            className={`px-5 py-2 rounded-lg text-[13px] font-bold transition-all duration-200 cursor-pointer whitespace-nowrap ${
                                                activeFilter === group ? 'bg-[var(--color-terciary)] text-[var(--color-text-value)] shadow-sm' : 'bg-transparent text-[var(--color-text-label)] hover:text-[var(--color-text-value)] hover:bg-[var(--color-secondary)]/50'
                                            }`}
                                        >
                                            {group}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </motion.div>
                )}

                {showOthers && (
                    <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: 'auto' }} className="flex flex-col gap-6 w-full pt-4">
                        <div className="grid grid-cols-2 lg:grid-cols-4 gap-6 w-full">
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

                        {/* Admin Ultra Filter Bar */}
                        <div className="flex flex-col gap-4 w-full mt-2">
                            <div className="flex flex-col lg:flex-row gap-4 w-full items-center">
                                <div className="relative flex-1 w-full p-[4px] rounded-2xl bg-gradient-to-br from-[var(--color-terciary)] via-[var(--color-secondary)] to-transparent focus-within:from-[var(--color-primary)] focus-within:via-[var(--color-primary)]/50 transition-all duration-500 ease-out shadow-sm focus-within:shadow-[var(--color-primary)]/10">
                                    <div className="relative bg-[var(--color-console)] backdrop-blur-xl rounded-[14px]">
                                        <svg className="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--color-text-sub)] w-4 h-4 transition-colors duration-300" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                        <input
                                            type="text"
                                            placeholder="Busca Ultra: Nome, ID, Dono, Email ou Grupo..."
                                            value={searchQuery}
                                            onChange={(e) => setSearchQuery(e.target.value)}
                                            className="w-full bg-transparent py-4 pl-11 pr-4 text-[13px] font-medium text-[var(--color-text-value)] placeholder:text-[var(--color-text-sub)]/50 focus:outline-none transition-all"
                                        />
                                    </div>
                                </div>

                                <div className="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                                    <div className="flex-1 sm:w-48 z-20 cursor-pointer">
                                        <Select options={statusOptions} value={adminStatusFilter} onChange={setAdminStatusFilter} />
                                    </div>
                                    <div className="flex-1 sm:w-48 z-10 cursor-pointer">
                                        <Select options={sortOptions} value={adminSortBy} onChange={setAdminSortBy} />
                                    </div>
                                </div>
                            </div>

                            {/* Group Filter for Admin */}
                            <div className="flex flex-col gap-2.5 w-full">
                                <span className="text-[10px] font-black text-[var(--color-text-sub)] uppercase tracking-widest pl-1">Filtrar Grupo Global</span>
                                <div className="p-[2px] rounded-xl bg-gradient-to-r from-[var(--color-terciary)]/40 to-transparent">
                                    <div className="flex gap-1 bg-[var(--color-secondary)]/60 p-1.5 rounded-[10px] overflow-x-auto custom-scrollbar">
                                        <button
                                            onClick={() => setAdminGroupFilter('all')}
                                            className={`px-5 py-2 rounded-lg text-[13px] font-bold transition-all duration-200 cursor-pointer whitespace-nowrap ${
                                                adminGroupFilter === 'all' ? 'bg-[var(--color-terciary)] text-[var(--color-text-value)] shadow-sm' : 'bg-transparent text-[var(--color-text-label)] hover:text-[var(--color-text-value)] hover:bg-[var(--color-secondary)]/50'
                                            }`}
                                        >
                                            Todos os Grupos
                                        </button>
                                        {groups.map(group => (
                                            <button
                                                key={group}
                                                onClick={() => setAdminGroupFilter(group)}
                                                className={`px-5 py-2 rounded-lg text-[13px] font-bold transition-all duration-200 cursor-pointer whitespace-nowrap ${
                                                    adminGroupFilter === group ? 'bg-[var(--color-terciary)] text-[var(--color-text-value)] shadow-sm' : 'bg-transparent text-[var(--color-text-label)] hover:text-[var(--color-text-value)] hover:bg-[var(--color-secondary)]/50'
                                                }`}
                                            >
                                                {group}
                                            </button>
                                        ))}
                                    </div>
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
                                                    className="px-6 py-3 rounded-[14px] bg-[var(--color-secondary)] text-[13px] font-bold text-[var(--color-text-value)] disabled:opacity-40 disabled:bg-transparent transition-colors shadow-sm cursor-pointer"
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
                                                    className="px-6 py-3 rounded-[14px] bg-[var(--color-secondary)] text-[13px] font-bold text-[var(--color-text-value)] disabled:opacity-40 disabled:bg-transparent transition-colors shadow-sm cursor-pointer"
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
                                            <h3 className="text-[14px] font-black text-[var(--color-text-value)] tracking-widest uppercase">{group}</h3>
                                            <div className="h-[1.5px] flex-1 bg-gradient-to-r from-[var(--color-terciary)] to-transparent opacity-40"></div>
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
                                    {showOthers
                                        ? 'Nenhum servidor corresponde aos filtros de administrador.'
                                        : 'Tente buscar com outros filtros ou termos.'}
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