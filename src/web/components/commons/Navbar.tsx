import React from 'react';
import { useSession } from "@vatts/auth/react";
import { Link, router, VattsImage } from "vatts/react";
import { motion } from "framer-motion";

const Navbar: React.FC = () => {
    const session = useSession();

    // @ts-ignore
    const panelName = typeof window !== 'undefined' && window.PanelSettings && window.PanelSettings.name ? window.PanelSettings.name : "Lunar Panel";
    const isDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    const urlImage = isDark ? '/assets/img/logo-white.png' : '/assets/img/logo-dark.png';

    const isSidebarActive = router.pathname.startsWith('/server');

    // Helper para classes dos itens da navegação
    const navItemClass = (isActive: boolean) => `
        relative h-full px-6 flex items-center transition-all duration-200 cursor-pointer
        ${isActive
        ? 'text-[var(--color-primary)] bg-white/[0.02]'
        : 'text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] hover:bg-white/[0.02]'
    }
    `;

    return (
        <nav className="w-full h-16 bg-[var(--color-navbar)]/90 backdrop-blur-md sticky top-0 z-[100]">
            {/* O motion.div com a prop 'layout' faz a transição entre o max-w-7xl e o max-w-full ser fluída */}
            <motion.div
                layout
                transition={{ type: "spring", stiffness: 300, damping: 30 }}
                className={`h-full flex items-center justify-between px-6 mx-auto ${isSidebarActive ? 'max-w-full w-full' : 'max-w-7xl w-full'}`}
            >

                {/* Logo - Esquerda */}
                <Link href={"/"} className="flex items-center gap-3 cursor-pointer group h-full">
                    <VattsImage src={urlImage} width={28} className="group-hover:scale-105 transition-transform duration-300" />

                    {/* Divisor vertical sutil */}
                    <div className="hidden sm:block w-[1px] h-5 bg-white/10 mx-1" />

                    <span className="text-[15px] text-[var(--color-text-value)] font-black tracking-tight group-hover:text-[var(--color-primary)] transition-colors truncate max-w-[150px] sm:max-w-none">
                        {/* @ts-ignore */}
                        {panelName}
                    </span>
                </Link>

                {/* Ícones de Navegação - Direita */}
                <div className="flex items-center h-full">

                    {/* Item Ativo (Servidores) */}
                    <Link href={"/"} className={navItemClass(router.pathname === "/")}>
                        <svg width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24" className="relative z-10">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 12 12 17 22 12"></polyline>
                            <polyline points="2 17 12 22 22 17"></polyline>
                        </svg>
                        {router.pathname === "/" && (
                            <div className="absolute bottom-0 left-0 w-full h-[2px] bg-[var(--color-primary)]" />
                        )}
                    </Link>

                    {/* Admin Link */}
                    {session.data?.user?.role === 'admin' && (
                        <a href="/admin" className={navItemClass(false)}>
                            <svg width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                        </a>
                    )}

                    {/* Perfil */}
                    <Link href={"/profile"} className={navItemClass(router.pathname.startsWith('/profile'))}>
                        <svg width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        {router.pathname.startsWith('/profile') && (
                            <div className="absolute bottom-0 left-0 w-full h-[2px] bg-[var(--color-primary)]" />
                        )}
                    </Link>

                    {/* Divisor Horizontal */}
                    <div className="w-[1px] h-6 bg-white/5 mx-2" />

                    {/* Logout */}
                    <button
                        onClick={() => session.signOut({callbackUrl: '/auth'})}
                        className="h-full px-6 flex items-center text-[var(--color-text-sub)] hover:text-[var(--color-danger)] hover:bg-red-500/10 transition-colors duration-200 cursor-pointer"
                        title="Sair"
                    >
                        <svg width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </button>
                </div>
            </motion.div>
        </nav>
    );
};

export default Navbar;