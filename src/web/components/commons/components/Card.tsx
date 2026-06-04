import React from "react";

type CardProps = {
    children: React.ReactNode
    title?: string;
    className?: string;
}

export default function Card({ children, title, className = "" } : CardProps) {
    return (
        /* Glow Wrapper: Removi o background daqui para não preencher o meio e estragar a transparência */
        <div className={`w-full group relative p-[4px] rounded-2xl transition-all duration-500 ease-out shadow-[var(--card-shadow)] hover:shadow-2xl flex flex-col ${className}`}>

            {/* Fake Border Layer: Fica em posição absoluta e usa máscara pra "vazar" o meio */}
            <div
                className="absolute inset-0 rounded-2xl bg-gradient-to-br from-[var(--color-terciary)] via-[var(--color-secondary)] to-transparent pointer-events-none transition-all duration-500 ease-out"
                style={{
                    padding: "4px",
                    WebkitMask: "linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0)",
                    WebkitMaskComposite: "xor",
                    maskComposite: "exclude"
                }}
            />

            {/* Inner Content: Exatamente como você pediu, agora a transparência do bg-black/10 vai pegar o fundo da tela */}
            <div className="relative flex flex-col h-full bg-(--color-secondary)/50 rounded-[14px] overflow-hidden transition-colors duration-500 ease-out">
                {title && (
                    <div className="px-6 py-5 text-[var(--color-text-sub)] text-[12px] font-bold uppercase tracking-wider bg-white/[0.02]">
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