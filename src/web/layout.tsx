import React from 'react';
import { Metadata } from "nytlex/react"
import './globals.css';
import { SessionProvider, useSession } from "@nytlex/auth/react";
import { ToastProvider } from "@/web/contexts/ToastContext";
import DashboardWrapper from "@/web/components/wrappers/Wrapper";

interface LayoutProps {
    children: React.ReactNode;
}

// @ts-ignore
const panelName = typeof window !== 'undefined' && window.PanelSettings && window.PanelSettings.name ? window.PanelSettings.name : "Lunar Panel";

export const metadata: Metadata = {
    title: `${panelName}`,
    description: "Painel de gerenciamento de servidores e aplicações.",
    keywords: ["Lunar Panel", "dashboard", "painel", "hosting", "gerenciamento"],
    author: "mfraz",
    faviconDark: "/assets/img/logo-white.png",
    favicon: "/assets/img/logo-dark.png",
    // Configurações importantes para painéis/PWA
    viewport: "width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0",
    themeColor: "#000000", // Modo escuro (preto puro)
    charset: "utf-8",
    language: "pt-BR",

    // Bloqueia indexação de motores de busca (Google, Bing) nas páginas do painel
    robots: "noindex, nofollow",

    // O básico de OpenGraph/Twitter.
    // Como é uma área privada, não vale a pena colocar imagens gigantes (summary_large_image).
    openGraph: {
        title: `${panelName}`,
        description: "Acesso ao painel de gerenciamento.",
        type: "website",
        locale: "pt_BR",
        siteName: `${panelName}`
    },
    twitter: {
        card: "summary",
        title: `${panelName}`,
        description: "Acesso ao painel de gerenciamento."
    }
};

export default function Layout({ children }: LayoutProps) {
    return (
        <ToastProvider>
            <SessionProvider>
                <DashboardWrapper>
                    {children}
                </DashboardWrapper>
            </SessionProvider>
        </ToastProvider>
    );
}