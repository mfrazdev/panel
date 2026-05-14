import React, { useEffect, useState } from 'react';
import { useSession } from "@vatts/auth/react";
import { Link, router, VattsImage } from "vatts/react";
import { motion } from "framer-motion";

const Navbar: React.FC = () => {
    const session = useSession();
    const [gravatarHash, setGravatarHash] = useState('');

    // @ts-ignore
    const panelName = typeof window !== 'undefined' && window.PanelSettings && window.PanelSettings.name ? window.PanelSettings.name : "Lunar Panel";
    const isDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    const urlImage = isDark ? '/assets/img/logo-white.png' : '/assets/img/logo-dark.png';

    const isSidebarActive = router.pathname.startsWith('/server');

    // Gera o hash SHA-256 nativo do navegador para o Gravatar (Sem precisar de libs extras como md5)
    useEffect(() => {
        const email = session.data?.user?.email;
        if (email) {
            const hashEmail = async () => {
                try {
                    const msgBuffer = new TextEncoder().encode(email.trim().toLowerCase());
                    const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
                    const hashArray = Array.from(new Uint8Array(hashBuffer));
                    const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
                    setGravatarHash(hashHex);
                } catch (e) {
                    console.error('Erro ao gerar hash do Gravatar:', e);
                }
            };
            hashEmail();
        }
    }, [session.data?.user?.email]);

    // Lógica para pegar first_name e last_name com fallback seguro
    const firstName = session.data?.user?.first_name || session.data?.user?.name?.split(' ')[0] || 'Usuário';
    const lastName = session.data?.user?.last_name || session.data?.user?.name?.split(' ').slice(1).join(' ') || '';

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
                        
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" className="lucide lucide-layout-dashboard-icon lucide-layout-dashboard"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
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

                    {/* Perfil Modernizado com Gravatar e Nome */}
                    <Link
                        href={"/profile"}
                        className={`relative h-full px-5 flex items-center transition-all duration-200 cursor-pointer group ${router.pathname.startsWith('/profile') ? 'bg-white/[0.02]' : 'hover:bg-white/[0.02]'}`}
                    >
                        <div className="flex items-center gap-3">
                            <img
                                src={`https://www.gravatar.com/avatar/${gravatarHash}?s=80&d=mp`}
                                alt="Avatar"
                                className={`w-8 h-8 rounded-full object-cover transition-colors duration-200 shadow-sm`}
                            />
                            <div className="hidden md:flex flex-col items-start justify-center">
                                <span className={`text-[13px] font-mono font-bold leading-none transition-colors duration-200 ${router.pathname.startsWith('/profile') ? 'text-[var(--color-primary)]' : 'text-[var(--color-text-value)] group-hover:text-[var(--color-primary)]'}`}>
                                    {firstName} {lastName}
                                </span>
                                <span className="text-[10px] font-mono font-bold text-[var(--color-text-sub)] mt-1.5 leading-none uppercase tracking-widest">
                                    Minha Conta
                                </span>
                            </div>
                        </div>
                        {router.pathname.startsWith('/profile') && (
                            <div className="absolute bottom-0 left-0 w-full h-[2px] bg-[var(--color-primary)]" />
                        )}
                    </Link>

                    {/* Divisor Horizontal */}
                    <div className="w-[1px] h-6 bg-white/5 mx-1" />

                    {/* Logout */}
                    <button
                        onClick={() => session.signOut({callbackUrl: '/auth'})}
                        className="h-full px-5 flex items-center text-[var(--color-text-sub)] hover:text-[var(--color-danger)] hover:bg-red-500/10 transition-colors duration-200 cursor-pointer"
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