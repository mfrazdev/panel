import React from "react";

type CardProps = {
    children: React.ReactNode
    title?: string;
    className?: string;
}

export default function Card({ children, title, className = "" } : CardProps) {
    return (
        <div className={`w-full bg-[var(--color-secondary)] border border-white/5 rounded-xl shadow-[var(--card-shadow)] flex flex-col ${className}`}>
            {title && (
                <div className="px-6 py-5 border-b border-white/10 text-[var(--color-text-sub)] text-[12px] font-bold uppercase tracking-wider bg-white/[0.01] rounded-t-xl">
                    {title}
                </div>
            )}

            <div className="p-6 text-[var(--color-text-label)]">
                {children}
            </div>
        </div>
    )
}