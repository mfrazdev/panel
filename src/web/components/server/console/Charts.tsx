import React, { useEffect, useState } from 'react';
import {
    AreaChart,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer
} from 'recharts';
import { useServerContext } from '../../../contexts/ServerContext';

// Função formatBytes forçando 0 casas decimais no eixo Y
const formatBytes = (bytes: number, decimals = 2) => {
    if (!+bytes) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
};

interface ChartDataPoint {
    time: string;
    memory: number;
    networkIn: number;
    networkOut: number;
}

const CustomTooltip = ({ active, payload, label, formatter }: any) => {
    if (active && payload && payload.length) {
        return (
            // Tooltip também clean, sem borda seca, usando as variáveis do root
            <div className="bg-[var(--color-secondary)] backdrop-blur-xl p-3 rounded-xl shadow-[var(--card-shadow)]">
                <p className="text-[10px] font-bold uppercase tracking-widest mb-1 text-[var(--color-text-label)]">
                    {label}
                </p>
                <p className="text-sm font-mono font-bold tracking-tight text-[var(--color-text-value)]">
                    {formatter ? formatter(payload[0].value) : payload[0].value}
                </p>
            </div>
        );
    }
    return null;
};

const Charts: React.FC = () => {
    const { usage } = useServerContext();
    const [dataHistory, setDataHistory] = useState<ChartDataPoint[]>([]);

    useEffect(() => {
        if (usage) {
            const now = new Date();
            const timeString = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}:${now.getSeconds().toString().padStart(2, '0')}`;

            // Verifica se o servidor parou para forçar o gráfico a cair para 0
            const isStopped = usage.state === 'stopped'

            setDataHistory((prev) => {
                const newPoint: ChartDataPoint = {
                    time: timeString,
                    memory: isStopped ? 0 : (usage.memory || 0),
                    networkIn: isStopped ? 0 : (usage.networkIn || 0),
                    networkOut: isStopped ? 0 : (usage.networkOut || 0),
                };
                const next = [...prev, newPoint];
                return next.length > 20 ? next.slice(next.length - 20) : next;
            });
        }
    }, [usage]);

    // Usado para zerar os textos grandes em cima do gráfico também
    const isStopped = usage?.state === 'stopped'

    const chartConfigs = [
        {
            id: 'memory',
            label: 'Memória RAM',
            value: formatBytes(isStopped ? 0 : (usage?.memory || 0), 2),
            color: 'var(--color-primary)',
            dataKey: 'memory',
            formatter: (val: number) => formatBytes(val, 2),
            axisFormatter: (val: number) => formatBytes(val, 0),
            icon: <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth="2.5"><path d="M4 21v-7m0-4V3m8 18v-9m0-4V3m8 18v-5m0-4V3M1 14h7m2-6h6m2 8h6" /></svg>
        },
        {
            id: 'networkIn',
            label: 'Rede (Entrada)',
            value: `${formatBytes(isStopped ? 0 : (usage?.networkIn || 0), 2)}/s`,
            color: 'var(--color-success)',
            dataKey: 'networkIn',
            formatter: (val: number) => `${formatBytes(val, 2)}/s`,
            axisFormatter: (val: number) => `${formatBytes(val, 0)}/s`,
            icon: <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth="2.5"><path d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
        },
        {
            id: 'networkOut',
            label: 'Rede (Saída)',
            value: `${formatBytes(isStopped ? 0 : (usage?.networkOut || 0), 2)}/s`,
            color: 'var(--color-info)',
            dataKey: 'networkOut',
            formatter: (val: number) => `${formatBytes(val, 2)}/s`,
            axisFormatter: (val: number) => `${formatBytes(val, 0)}/s`,
            icon: <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth="2.5"><path d="M11 17l-5-5m0 0l5-5m-5 5h12" /></svg>
        },
    ];

    return (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {chartConfigs.map((chart) => (
                /* Glow Wrapper - Substituindo a borda real pelo padding fake animado (p-[4px]) */
                <div
                    key={chart.id}
                    className="group relative p-[4px] rounded-2xl bg-gradient-to-br from-[var(--color-terciary)] via-[var(--color-secondary)] to-transparent transition-all duration-500 ease-out shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transform flex flex-col h-full"
                >
                    {/* Inner Container - Fundo secundário sem borda */}
                    <div className="relative flex flex-col h-full bg-[var(--color-secondary)] backdrop-blur-xl rounded-[15px] p-4 transition-colors duration-500 ease-out">

                        <div className="flex items-center gap-4 mb-4">
                            {/* Caixa do ícone agora com cor terciária do root e sem borda */}
                            <div
                                className="w-12 h-12 rounded-xl bg-[var(--color-terciary)] flex items-center justify-center transition-all duration-300 ease-out shadow-sm shrink-0"
                                style={{ color: chart.color }}
                            >
                                <div className="scale-90 group-hover:scale-105 transition-transform duration-300 ease-out">
                                    {chart.icon}
                                </div>
                            </div>

                            <div className="flex flex-col flex-1 min-w-0">
                                <span className="text-[10px] text-[var(--color-text-sub)] uppercase font-black tracking-widest mb-0.5 truncate block w-full transition-colors duration-300 ease-out">
                                    {chart.label}
                                </span>
                                <span className="text-xl font-mono text-[var(--color-text-value)] font-medium tracking-tight truncate max-w-full transition-colors duration-300 ease-out">
                                    {chart.value}
                                </span>
                            </div>
                        </div>

                        <div className="h-40 w-full">
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={dataHistory} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                                    <defs>
                                        <linearGradient id={`color${chart.id}`} x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="5%" stopColor={chart.color} stopOpacity={0.3}/>
                                            <stop offset="95%" stopColor={chart.color} stopOpacity={0}/>
                                        </linearGradient>
                                    </defs>
                                    {/* Linhas de fundo mais sutis para não poluir */}
                                    <CartesianGrid strokeDasharray="3 3" stroke="rgba(161, 161, 170, 0.15)" vertical={false} />
                                    <XAxis
                                        dataKey="time"
                                        tick={false}
                                        axisLine={false}
                                        tickLine={false}
                                    />
                                    <YAxis
                                        width={65}
                                        stroke="rgba(161, 161, 170, 0.5)"
                                        fontSize={10}
                                        fontFamily="monospace"
                                        tickLine={false}
                                        axisLine={false}
                                        tickCount={4}
                                        tickFormatter={(val: any) => chart.axisFormatter(val)}
                                    />
                                    <Tooltip content={<CustomTooltip formatter={chart.formatter} />} cursor={{ stroke: 'rgba(161, 161, 170, 0.2)', strokeWidth: 1 }} />
                                    <Area
                                        type="monotone"
                                        dataKey={chart.dataKey}
                                        stroke={chart.color}
                                        strokeWidth={2}
                                        fillOpacity={1}
                                        fill={`url(#color${chart.id})`}
                                        isAnimationActive={false}
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>

                    </div>
                </div>
            ))}
        </div>
    );
};

export default Charts;