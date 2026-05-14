import { motion, AnimatePresence } from "framer-motion";
import React, { useState, useEffect } from 'react';
import {
    ChevronLeft,
    ChevronRight,
    ShieldCog,
    ChartNoAxesColumnIncreasing,
    Play,
    Clock, EthernetPort
} from "lucide-react";
import { useSession } from "@vatts/auth/react";
import { useServerContext } from "@/web/contexts/ServerContext";

type Sidebar = {
    serverId: string;
    activeTab: string;
    changeAction: any
}

const menuCategories = [
    {
        title: "Principal",
        items: [
            { id: 'console', name: 'Console', icon: <path d="M4 17l6-6-6-6M12 19h8" /> },
            { id: 'files', name: 'Arquivos', icon: <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" /> },
            { id: 'database', name: 'Bancos de Dados', icon: <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5 M21 12c0 1.66-4 3-9 3s-9-1.34-9-3 M12 8c-4.97 0-9-1.34-9-3s4.03-3 9-3 9 1.34 9 3-4.03 3-9 3z" /> },
        ]
    },
    {
        title: "Gerenciamento",
        items: [
            { id: 'allocations', name: 'Rede', icon: <EthernetPort width="20" height="20" strokeWidth="2" /> },
            { id: 'schedulers', name: 'Agendamentos', icon: <Clock width="20" height="20" strokeWidth="2" /> },
        ]
    },
    {
        title: "Avançado",
        items: [
            { id: 'startup', name: 'Inicialização', icon: <Play width="20" height="20" strokeWidth="2" />},
            { id: 'settings', name: 'Configurações', icon: <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" /> },
        ]
    }
];

export default function ServerSidebar({ serverId, activeTab, changeAction }: Sidebar) {
    const [isCollapsed, setIsCollapsed] = useState(false);
    const [isMounted, setIsMounted] = useState(false);
    const user = useSession()
    const serverContext = useServerContext()

    useEffect(() => {
        setIsMounted(true);
        const storedValue = localStorage.getItem('@hightcloud:sidebar-collapsed');
        if (storedValue) {
            setIsCollapsed(storedValue === 'true');
        }
    }, []);

    const handleToggle = () => {
        const newValue = !isCollapsed;
        setIsCollapsed(newValue);
        localStorage.setItem('@hightcloud:sidebar-collapsed', String(newValue));
    };

    if (!isMounted) return <aside className="w-[260px] h-[calc(100vh-4rem)] shrink-0 bg-[var(--color-sidebar)] border-r border-white/5" />;

    const isSuspended = serverContext.server?.suspended === 1;

    return (
        // Altere apenas o className do motion.aside principal para remover a borda:
        <motion.aside
            initial={false}
            animate={{ width: isCollapsed ? 72 : 260 }}
            transition={{ type: "spring", stiffness: 300, damping: 30 }}
            className="sticky top-16 h-[calc(100vh-4rem)] shrink-0 bg-[var(--color-sidebar)] flex flex-col pt-2 pb-4 z-40 overflow-hidden"
        >
            <div className="flex-1 overflow-y-auto custom-scrollbar overflow-x-hidden flex flex-col gap-0.5">
                {menuCategories.map((category, catIndex) => (
                    <div key={category.title}>

                        <AnimatePresence mode="wait">
                            {!isCollapsed ? (
                                <motion.div
                                    key="title-full"
                                    initial={{ opacity: 0 }}
                                    animate={{ opacity: 1 }}
                                    exit={{ opacity: 0 }}
                                    className="flex items-center justify-between px-5 pt-6 pb-2 text-[12px] font-semibold text-[var(--color-text-sub)] capitalize"
                                >
                                    {category.title}
                                </motion.div>
                            ) : (
                                <motion.div
                                    key="title-collapsed"
                                    initial={{ opacity: 0 }}
                                    animate={{ opacity: 1 }}
                                    exit={{ opacity: 0 }}
                                    className="pt-6 pb-2 flex justify-center"
                                >
                                    <div className="w-4 h-[2px] rounded-full bg-[var(--color-text-sub)] opacity-30" />
                                </motion.div>
                            )}
                        </AnimatePresence>

                        {/* Lista de Itens */}
                        <div className="flex flex-col">
                            {category.items.map((tab) => {
                                const isActive = activeTab === tab.id;
                                const isDisabled = isSuspended && tab.id !== 'console';

                                return (
                                    <a
                                        href={isActive ? `/server/${serverId}` : `/server/${serverId}/${tab.id}`}
                                        key={tab.id}
                                        onClick={(event) => {
                                            event.preventDefault();
                                            if (isDisabled) return;
                                            changeAction(tab.id);
                                        }}
                                        className={`group relative flex items-center py-2.5 mx-3 my-[2px] rounded-lg text-[13.5px] font-medium transition-all duration-200 ${
                                            isCollapsed ? 'justify-center px-0' : 'gap-[14px] px-4'
                                        } ${
                                            isActive
                                                ? 'bg-[var(--color-secondary)] text-[var(--color-primary)] shadow-[var(--card-shadow)]'
                                                : isDisabled
                                                    ? 'opacity-30 cursor-not-allowed text-[var(--color-text-label)]'
                                                    : 'text-[var(--color-text-label)] hover:bg-[var(--color-secondary)] hover:text-[var(--color-text-value)] hover:shadow-[var(--card-shadow)]'
                                        }`}
                                    >
                                        <svg
                                            width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" viewBox="0 0 24 24"
                                            className="shrink-0 transition-colors duration-200"
                                            style={{ color: isActive ? 'var(--color-primary)' : 'inherit' }}
                                        >
                                            {tab.icon}
                                        </svg>

                                        {!isCollapsed && (
                                            <motion.span
                                                initial={{ opacity: 0 }}
                                                animate={{ opacity: 1 }}
                                                className="whitespace-nowrap transition-colors"
                                            >
                                                {tab.name}
                                            </motion.span>
                                        )}
                                    </a>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </div>

            {/* Ações da base (Admin & Toggle) */}
            <div className="mt-auto pt-4 flex flex-col gap-1 border-t border-white/5">
                {user.data?.user.role == 'admin' && (
                    <a
                        href={`/admin/servers/${serverContext.server?.id}/edit`}
                        className={`group relative flex items-center py-2.5 mx-3 my-[2px] rounded-lg text-[13.5px] font-medium transition-all duration-200 text-[var(--color-text-label)] hover:bg-[var(--color-secondary)] hover:text-[var(--color-text-value)] hover:shadow-[var(--card-shadow)] ${
                            isCollapsed ? 'justify-center px-0' : 'justify-between px-4'
                        }`}
                    >
                        {!isCollapsed && (
                            <motion.span
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                className="whitespace-nowrap transition-colors"
                            >
                                Administração
                            </motion.span>
                        )}
                        <ShieldCog width="18" height="18" strokeWidth="2" className="shrink-0 text-[var(--color-text-sub)] group-hover:text-[var(--color-text-label)] transition-colors" />
                    </a>
                )}

                <button
                    onClick={handleToggle}
                    className={`group relative flex items-center py-2.5 mx-3 my-[2px] rounded-lg text-[13.5px] font-medium transition-all duration-200 text-[var(--color-text-label)] hover:bg-[var(--color-secondary)] hover:text-[var(--color-text-value)] hover:shadow-[var(--card-shadow)] ${
                        isCollapsed ? 'justify-center px-0' : 'justify-between px-4'
                    }`}
                >
                    {!isCollapsed && (
                        <motion.span
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            className="whitespace-nowrap transition-colors"
                        >
                            Recolher Menu
                        </motion.span>
                    )}
                    {isCollapsed ? (
                        <ChevronRight width="18" height="18" strokeWidth="2" className="shrink-0 text-[var(--color-text-sub)] group-hover:text-[var(--color-text-label)] transition-colors" />
                    ) : (
                        <ChevronLeft width="18" height="18" strokeWidth="2" className="shrink-0 text-[var(--color-text-sub)] group-hover:text-[var(--color-text-label)] transition-colors" />
                    )}
                </button>
            </div>
        </motion.aside>
    );
}