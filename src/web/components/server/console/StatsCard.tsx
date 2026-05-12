import React from 'react';

interface StatCardProps {
    label: string;
    value: string;
    subValue?: string;
    icon: React.ReactNode;
    accentColor?: string;
    labelColor?: string;
    valueColor?: string;
    subValueColor?: string;
}

const StatsCard: React.FC<StatCardProps> = ({
                                                label,
                                                value,
                                                subValue,
                                                icon,
                                                accentColor = 'var(--color-primary)',
                                                labelColor = 'var(--color-text-label)',
                                                valueColor = 'var(--color-text-value)',
                                                subValueColor = 'var(--color-text-sub)'
                                            }) => {
    return (
        /* O @container avisa ao Tailwind para observar a largura deste elemento pai */
        <div className="@container w-full min-w-0">
            <div className="group flex items-center gap-3 @[16rem]:gap-4 p-3 @[16rem]:p-4 rounded-xl backdrop-blur-xl bg-(--color-secondary) transition-all duration-300 shadow-[var(--card-shadow)]">

                <div
                    className="w-10 h-10 @[16rem]:w-12 @[16rem]:h-12 rounded-lg flex items-center justify-center bg-(--color-terciary) transition-colors flex-shrink-0"
                    style={{ color: accentColor }}
                >
                    <div className="scale-90 @[16rem]:scale-110 opacity-80 group-hover:opacity-100 transition-opacity">
                        {icon}
                    </div>
                </div>

                <div className="flex flex-col flex-1 min-w-0">
                    <span
                        className="text-[9px] @[16rem]:text-[10px] font-bold uppercase tracking-widest mb-0.5 truncate block w-full"
                        style={{ color: labelColor }}
                        title={label}
                    >
                        {label}
                    </span>

                    <div className="flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5 min-w-0 w-full">
                        <span
                            className="text-lg @[16rem]:text-2xl font-bold tracking-tighter leading-none truncate max-w-full"
                            style={{ color: valueColor }}
                            title={value}
                        >
                            {value}
                        </span>

                        {subValue && (
                            <span
                                className="text-[10px] @[16rem]:text-xs font-medium tracking-tight whitespace-nowrap truncate max-w-full"
                                style={{ color: subValueColor }}
                                title={subValue}
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