import React, { useEffect, useState } from 'react';
import { useSession } from "@nytlex/auth/react";
import { Link, router, NytlexImage } from "nytlex/react";
import { motion, AnimatePresence } from "framer-motion";

const Navbar: React.FC = () => {
    const session = useSession();
    const [gravatarHash, setGravatarHash] = useState('');

    // @ts-ignore
    const panelName = typeof window !== 'undefined' && window.PanelSettings && window.PanelSettings.name ? window.PanelSettings.name : "Lunar Panel";
    const isDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    const urlImage = isDark ? '/assets/img/logo-white.png' : '/assets/img/logo-dark.png';

    const isSidebarActive = router.pathname.startsWith('/server');

    // Gera o hash SHA-256 nativo do navegador para o Gravatar
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

    const firstName = session.data?.user?.first_name || session.data?.user?.name?.split(' ')[0] || 'Usuário';
    const lastName = session.data?.user?.last_name || session.data?.user?.name?.split(' ').slice(1).join(' ') || '';

    // Helper limpo pros itens de navegação (Visíveis sempre, sem frescura de hover pra aparecer)
    const navItemClass = (isActive: boolean) => `
        relative flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl transition-all duration-300 cursor-pointer font-bold text-sm flex-shrink-0
        ${isActive
        ? 'text-[var(--color-primary)] bg-[var(--color-primary)]/10 shadow-[inset_0_0_15px_rgba(156,59,246,0.1)]'
        : 'text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] hover:bg-white/5'
    }
    `;

    return (
        /* Container fixo e invisível pro clique passar direto no fundo, espaçado do topo pra flutuar */
        <div className="mt-[16px] left-0 w-full z-[100] px-4 md:px-6 pointer-events-none flex justify-center">

            {/* Wrapper com a BORDA FAKE e Sombra Cabulosa */}
            <motion.div
                layout
                initial={false}
                animate={{ maxWidth: isSidebarActive ? "100%" : "72rem" }} // 72rem = max-w-6xl
                transition={{ type: "spring", stiffness: 300, damping: 30 }}
                className="pointer-events-auto p-[4px] rounded-[18px] bg-gradient-to-br from-[var(--color-terciary)] via-[var(--color-secondary)] to-transparent shadow-[0_15px_35px_rgba(0,0,0,0.6)] flex w-full"
            >
                {/* Navbar Interna: Fundo Glassmorphism Preto/Transparente. Adicionado overflow-x-auto com scrollbar escondida para mobile extremo */}
                <nav className="w-full h-16 bg-[var(--color-navbar)]/70 backdrop-blur-2xl rounded-[16px] flex items-center justify-between px-3 md:px-5 overflow-x-auto overflow-y-hidden [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">

                    {/* Logo - Esquerda */}
                    <Link href={"/"} className="flex items-center gap-3 cursor-pointer group h-full px-2 flex-shrink-0">
                        <div className="p-1.5 rounded-xl bg-white/5 group-hover:bg-white/10 group-hover:shadow-[0_0_15px_rgba(255,255,255,0.1)] transition-all duration-300">
                            <NytlexImage src={urlImage} width={24} className="group-hover:scale-110 transition-transform duration-500" />
                        </div>

                        <span className="text-[15px] text-[var(--color-text-value)] font-black tracking-tight group-hover:text-[var(--color-text-sub)] transition-colors truncate hidden sm:block whitespace-nowrap">
                            {/* @ts-ignore */}
                            {panelName}
                        </span>
                    </Link>

                    {/* Navegação e Perfil - Direita */}
                    <div className="flex items-center gap-1.5 h-full py-2 flex-nowrap">

                        {/* Item Ativo (Painel/Servidores) */}
                        <Link href={"/"} className={navItemClass(router.pathname === "/")}>
                            <i className="fa-solid fa-server"></i>
                        </Link>

                        {/* Admin Link */}
                        {session.data?.user?.role === 'admin' && (
                            <a href="/admin" className={navItemClass(false)}>
                                <i className="fa-solid fa-users-gear"></i>
                            </a>
                        )}

                        {/* Divisor Vertical */}
                        <div className="w-[1px] h-6 bg-[var(--color-terciary)]/50 mx-2 rounded-full flex-shrink-0" />

                        {/* Conta do Usuário com AnimatePresence para transição suave */}
                        <AnimatePresence mode="popLayout">
                            {!isSidebarActive && (
                                <motion.div
                                    initial={{ opacity: 0, width: 0, scale: 0.8 }}
                                    animate={{ opacity: 1, width: "auto", scale: 1 }}
                                    exit={{ opacity: 0, width: 0, scale: 0.8 }}
                                    transition={{ type: "spring", stiffness: 300, damping: 30 }}
                                    className="overflow-hidden flex-shrink-0"
                                >
                                    <Link
                                        href={"/profile"}
                                        className={`relative flex items-center gap-3 px-3 py-1.5 rounded-xl transition-all duration-300 cursor-pointer group whitespace-nowrap ${router.pathname.startsWith('/profile') ? 'bg-white/5' : 'hover:bg-white/[0.04]'}`}
                                    >
                                        <img
                                            src={`https://www.gravatar.com/avatar/${gravatarHash}?s=80&d=mp`}
                                            alt="Avatar"
                                            className="w-8 h-8 rounded-full object-cover shadow-sm border border-white/10 group-hover:border-primary/50 transition-colors flex-shrink-0"
                                        />
                                        <div className="hidden lg:flex flex-col items-start justify-center">
                                            <span className="text-[15px] font-sans leading-none transition-colors duration-300 text-[var(--color-text-value)] hover:text-[var(--color-text-sub)]">
                                                {firstName} {lastName}
                                            </span>
                                        </div>
                                    </Link>
                                </motion.div>
                            )}
                        </AnimatePresence>

                        {/* Logout (Vermelho cabuloso no hover) */}
                        <button
                            onClick={() => session.signOut({callbackUrl: '/auth'})}
                            className="p-2.5 ml-1 rounded-xl text-[var(--color-text-sub)] hover:text-white hover:bg-danger/80 hover:shadow-[0_0_15px_rgba(239,68,68,0.4)] transition-all duration-300 cursor-pointer flex items-center justify-center flex-shrink-0"
                            title="Sair"
                        >
                            <svg width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                        </button>
                    </div>
                </nav>
            </motion.div>
        </div>
    );
};

export default Navbar;