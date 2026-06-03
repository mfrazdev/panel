import React from "react";

type CardProps = {
    children: React.ReactNode
    title?: string;
    className?: string;
}

export default function Card({ children, title, className = "" } : CardProps) {
    return (
        /* Glow Wrapper: Borda falsa com transição e leve hover */
        <div className={`w-full group relative p-[4px] rounded-2xl bg-gradient-to-br from-[var(--color-terciary)] via-[var(--color-secondary)] to-transparent transition-all duration-500 ease-out shadow-[var(--card-shadow)] hover:shadow-2xl flex flex-col ${className}`}>
            {/* Inner Content: Fundo limpo sem bordas reais */}
            <div className="relative flex flex-col h-full bg-[var(--color-secondary)] backdrop-blur-xl rounded-[14px] overflow-hidden transition-colors duration-500 ease-out">
                {title && (
                    <div className="px-6 py-5 border-b border-white/5 text-[var(--color-text-sub)] text-[12px] font-bold uppercase tracking-wider bg-white/[0.01]">
                        {title}
                    </div>
                )}

                <div className="p-6 text-[var(--color-text-label)]">
                    {children}
                </div>
            </div>
        </div>
    )
}