import { motion, AnimatePresence } from "framer-motion";
import React, { useState, useEffect } from 'react';
import {
    ListStartIcon,
    ChevronLeft,
    ChevronRight,
    ShieldCog,
    ChartNoAxesColumnIncreasing,
    Play,
    UsersRound
} from "lucide-react";
import {useSession} from "@vatts/auth/react";
import {useServerContext} from "@/web/contexts/ServerContext";

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
            { id: 'allocations', name: 'Rede', icon: <ChartNoAxesColumnIncreasing /> },
        ]
    },
    {
        title: "Avançado",
        items: [
            { id: 'startup', name: 'Inicialização', icon: <Play />},
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

    if (!isMounted) return <aside className="w-72 h-[calc(100vh-4rem)] shrink-0 bg-(--color-sidebar)" />;

    const isSuspended = serverContext.server?.suspended === 1;

    return (
        <motion.aside
            initial={false}
            animate={{ width: isCollapsed ? 90 : 288 }}
            transition={{ type: "spring", stiffness: 300, damping: 30 }}
            className="sticky top-16 h-[calc(100vh-4rem)] shrink-0 bg-(--color-sidebar) shadow-(--card-shadow) flex flex-col pt-4 pb-4 z-40"
        >
            <div className="flex-1 overflow-y-auto custom-scrollbar px-4 overflow-x-hidden">
                {menuCategories.map((category, catIndex) => (
                    <div key={category.title} className={`${catIndex !== 0 ? 'mt-8' : ''}`}>

                        <AnimatePresence mode="wait">
                            {!isCollapsed ? (
                                <motion.div
                                    key="title-full"
                                    initial={{ opacity: 0 }}
                                    animate={{ opacity: 0.5 }}
                                    exit={{ opacity: 0 }}
                                    className="px-4 mb-3 text-[10px] font-black tracking-[0.25em] uppercase whitespace-nowrap"
                                    style={{ color: 'var(--color-text-label)' }}
                                >
                                    {category.title}
                                </motion.div>
                            ) : (
                                <motion.div
                                    key="title-collapsed"
                                    initial={{ opacity: 0 }}
                                    animate={{ opacity: 0.3 }}
                                    exit={{ opacity: 0 }}
                                    className="mb-3 flex justify-center"
                                >
                                    <div className="w-4 h-[2px] rounded-full bg-current opacity-50" style={{ color: 'var(--color-text-label)' }} />
                                </motion.div>
                            )}
                        </AnimatePresence>

                        {/* Lista de Itens */}
                        <div className="flex flex-col gap-1 p-2 rounded-2xl bg-black/10 shadow-inner">
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
                                        className={`group relative flex items-center ${isCollapsed ? 'justify-center px-0' : 'gap-4 px-4'} py-3 rounded-xl text-sm font-bold transition-all duration-300 ${
                                            isActive
                                                ? 'bg-white/[0.04]'
                                                : isDisabled
                                                    ? 'opacity-30 cursor-not-allowed'
                                                    : 'hover:bg-white/[0.02]'
                                        }`}
                                        style={{ color: isActive ? 'var(--color-text-value)' : 'var(--color-text-label)' }}
                                    >
                                        {isActive && (
                                            <motion.div
                                                layoutId="activeSidebarLine"
                                                className="absolute left-0 top-1/4 bottom-1/4 w-[3px] rounded-r-full shadow-[0_0_15px_var(--color-primary)]"
                                                style={{ backgroundColor: 'var(--color-primary)' }}
                                            />
                                        )}

                                        <svg
                                            width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24"
                                            className="shrink-0 relative z-10"
                                            style={{ color: isActive ? 'var(--color-primary)' : 'currentColor' }}
                                        >
                                            {tab.icon}
                                        </svg>

                                        {!isCollapsed && (
                                            <motion.span
                                                initial={{ opacity: 0 }}
                                                animate={{ opacity: 1 }}
                                                className="tracking-wide whitespace-nowrap relative z-10"
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
            {user.data?.user.role == 'admin' && (
                <div className="mt-auto pt-4 px-4">
                    <a
                        href={`/admin/servers/${serverContext.server?.id}/edit`}
                        className={`flex items-center w-full py-3 rounded-xl text-sm font-bold transition-all duration-300 hover:bg-white/[0.04] ${
                            isCollapsed ? 'justify-center' : 'justify-between px-4'
                        }`}
                        style={{ color: 'var(--color-text-label)' }}
                    >
                        {!isCollapsed && (
                            <motion.span
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                className="whitespace-nowrap"
                            >
                                Administração
                            </motion.span>
                        )}
                        <ShieldCog size={20} />
                    </a>
                </div>
            )}
            {/* Rodapé da Sidebar */}
            <div className="mt-auto pt-4 px-4">
                <button
                    onClick={handleToggle}
                    className={`flex items-center w-full py-3 rounded-xl text-sm font-bold transition-all duration-300 hover:bg-white/[0.04] ${
                        isCollapsed ? 'justify-center' : 'justify-between px-4'
                    }`}
                    style={{ color: 'var(--color-text-label)' }}
                >
                    {!isCollapsed && (
                        <motion.span
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            className="whitespace-nowrap"
                        >
                            Recolher Menu
                        </motion.span>
                    )}
                    {isCollapsed ? <ChevronRight size={20} /> : <ChevronLeft size={20} />}
                </button>
            </div>
        </motion.aside>
    );
}