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
        /* O @container avisa ao Tailwind para observar a largura deste elemento pai */
        <div className="@container w-full min-w-0">
            <div className="group flex items-center gap-3 @[16rem]:gap-4 p-3 @[16rem]:p-4 bg-[var(--color-secondary)] border border-white/5 rounded-xl  hover:border-white/10 transition-all duration-300 shadow-sm">

                {/* Ícone Minimalista com background e borda sutil igual ao ServerRow */}
                <div className="w-10 h-10 @[16rem]:w-12 @[16rem]:h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-(--color-primary) transition-colors shadow-sm shrink-0">
                    <div className="scale-90 @[16rem]:scale-100 transition-transform duration-300">
                        {icon}
                    </div>
                </div>

                <div className="flex flex-col flex-1 min-w-0">
                    {/* Label utilizando uppercase font-black igual aos cards internos do ServerRow */}
                    <span
                        className="text-[9px] @[16rem]:text-[10px] text-[var(--color-text-sub)] uppercase font-black tracking-widest mb-0.5 truncate block w-full"
                        title={label}
                    >
                        {label}
                    </span>

                    <div className="flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5 min-w-0 w-full mt-0.5">
                        {/* Value utilizando font-mono igual aos status do sistema */}
                        <span
                            className="text-[15px] @[16rem]:text-xl font-mono text-[var(--color-text-value)] font-medium tracking-tight truncate max-w-full"
                            title={value}
                        >
                            {value}
                        </span>

                        {/* SubValue mantendo a padronização */}
                        {subValue && (
                            <span
                                className="inline-flex items-center text-[10px] @[16rem]:text-[11px] font-mono font-medium text-[var(--color-text-sub)] whitespace-nowrap truncate max-w-full"
                            >
                                <span className="opacity-30 mr-1">/</span>
                                {subValue}
                            </span>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default StatsCard;