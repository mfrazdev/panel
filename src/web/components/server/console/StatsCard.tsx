import React from 'react';

interface StatCardProps {
    label: string;
    value: string;
    subValue?: string | React.ReactNode;
    icon: React.ReactNode;
}

const StatsCard: React.FC<StatCardProps> = ({
                                                label,
                                                value,
                                                subValue,
                                                icon,
                                            }) => {
    return (
        /* O @container é crucial aqui pra ele saber se encolher dependendo do espaço da grid */
        <div className="@container w-full min-w-0 h-full">
            {/* Glow Wrapper com p-[4px] - Agora com transition-all pra animar o gradiente da borda fake */}
            <div className="group relative p-[4px] rounded-2xl bg-gradient-to-br from-[var(--color-terciary)] via-[var(--color-secondary)] to-transparent transition-all duration-500 ease-out shadow-xl h-full block w-full hover:shadow-2xl hover:-translate-y-0.5 transform">

                {/* Inner Card - Adicionado transition-colors para suavizar a troca de tema (light/dark) */}
                <div className="relative h-full bg-[var(--color-secondary)] backdrop-blur-xl rounded-[15px] p-3 @[16rem]:p-4 flex items-center gap-3 @[16rem]:gap-4 transition-colors duration-500 ease-out">

                    {/* Icon Box - Adicionado transition-all para animar fundo e sombra junto */}
                    <div className="w-10 h-10 @[16rem]:w-12 @[16rem]:h-12 rounded-xl bg-[var(--color-terciary)] text-[var(--color-primary)] group-hover:text-[var(--color-text-value)] group-hover:bg-[var(--color-terciary)]/80 transition-all duration-300 ease-out shadow-sm shrink-0 flex items-center justify-center">
                        <div className="scale-90 @[16rem]:scale-100 transition-transform duration-300 ease-out group-hover:scale-105">
                            {icon}
                        </div>
                    </div>

                    {/* Content */}
                    <div className="flex flex-col flex-1 min-w-0 justify-center">
                        <span
                            className="text-[9px] @[16rem]:text-[10px] text-[var(--color-text-label)] uppercase font-black tracking-widest mb-0.5 truncate block w-full transition-colors duration-300 ease-out"
                            title={label}
                        >
                            {label}
                        </span>

                        <div className="flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5 min-w-0 w-full mt-0.5">
                            {/* Value */}
                            <span
                                className="text-[15px] @[16rem]:text-xl font-mono text-[var(--color-text-value)] font-black tracking-tight truncate max-w-full leading-none transition-colors duration-300 ease-out"
                                title={value}
                            >
                                {value}
                            </span>

                            {subValue && (
                                <span className="inline-flex items-center text-[10px] @[16rem]:text-[11px] font-mono font-bold text-[var(--color-text-sub)] whitespace-nowrap truncate max-w-full transition-colors duration-300 ease-out">
                                    <span className="opacity-40 mr-1">/</span>
                                    {subValue}
                                </span>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default StatsCard;