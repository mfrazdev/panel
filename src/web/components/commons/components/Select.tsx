import React, { useState, useRef, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { ChevronDown } from "lucide-react";

type Option = {
    label: string;
    value: string | number;
};

type SelectProps = {
    label?: string | null;
    desc?: string | null;
    options: Option[];
    value?: string | number;
    onChange: (value: any) => void;
    placeholder?: string;
    className?: string;
};

export default function Select({ label, desc, options, value, onChange, placeholder = "Selecione...", className }: SelectProps) {
    const [isOpen, setIsOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    // Fecha ao clicar fora
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };
        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    const selectedOption = options.find(opt => opt.value === value);

    return (
        <div className="flex flex-col gap-1.5 w-full" ref={containerRef}>
            {label && (
                <label className="font-bold text-[var(--color-text-sub)] text-[12px] uppercase tracking-wider ml-1">
                    {label}
                </label>
            )}

            {/* Z-index dinâmico: sobe para 50 apenas quando está aberto para não sobrepor outros inputs inativos */}
            <div className={`relative w-full ${isOpen ? 'z-50' : 'z-10'}`}>

                {/* Glow Wrapper do Trigger: Acende com a cor primária se estiver aberto ou em focus */}
                <div className={`group relative p-[4px] rounded-2xl transition-all duration-500 ease-out shadow-sm ${
                    isOpen
                        ? 'bg-gradient-to-br from-[var(--color-primary)] via-[var(--color-primary)]/50 to-transparent shadow-[var(--color-primary)]/10'
                        : 'bg-gradient-to-br from-[var(--color-terciary)] via-[var(--color-secondary)] to-transparent hover:from-[var(--color-text-sub)]/30 focus-within:from-[var(--color-primary)] focus-within:via-[var(--color-primary)]/50 focus-within:shadow-[var(--color-primary)]/10'
                }`}>
                    {/* Trigger (O "Botão" do Select) - Padrão do Input, sem bordas */}
                    <button
                        type="button"
                        onClick={() => setIsOpen(!isOpen)}
                        className={`
                            w-full bg-[var(--color-console)] text-[var(--color-text-value)] 
                            px-4 py-3 rounded-[14px] border-none outline-none flex items-center justify-between
                            transition-colors duration-300 cursor-pointer shadow-inner
                            ${className || ""}
                        `}
                    >
                        <span className={`text-[14px] font-medium transition-colors ${!selectedOption ? "text-[var(--color-text-sub)]/50" : ""}`}>
                            {selectedOption ? selectedOption.label : placeholder}
                        </span>
                        <motion.div
                            animate={{ rotate: isOpen ? 180 : 0 }}
                            transition={{ duration: 0.2 }}
                            className="flex items-center"
                        >
                            <ChevronDown size={18} className={`transition-colors duration-300 ${isOpen ? 'text-[var(--color-primary)]' : 'text-[var(--color-text-sub)] group-hover:text-[var(--color-text-value)]'}`} />
                        </motion.div>
                    </button>
                </div>

                {/* Dropdown Menu com AnimatePresence */}
                <AnimatePresence>
                    {isOpen && (
                        /* Wrapper Animado do Dropdown com Borda Fake (p-[2px]) */
                        <motion.div
                            initial={{ opacity: 0, y: -10, scale: 0.98 }}
                            animate={{ opacity: 1, y: 5, scale: 1 }}
                            exit={{ opacity: 0, y: -10, scale: 0.98 }}
                            transition={{ duration: 0.15, ease: "easeOut" }}
                            className="absolute left-0 w-full p-[2px] bg-gradient-to-br from-[var(--color-terciary)] via-[var(--color-secondary)] to-transparent rounded-2xl shadow-2xl mt-1 overflow-hidden"
                        >
                            {/* Lista Interna sem bordas reais */}
                            <ul className="w-full bg-[var(--color-secondary)] backdrop-blur-xl rounded-[14px] p-1.5 max-h-[250px] overflow-y-auto custom-scrollbar flex flex-col gap-1">
                                {options.length > 0 ? (
                                    options.map((option) => (
                                        <li key={option.value}>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    onChange(option.value);
                                                    setIsOpen(false);
                                                }}
                                                className={`
                                                    cursor-pointer w-full text-left p-3 rounded-xl text-[14px] font-medium transition-all duration-200
                                                    ${value === option.value
                                                    ? "bg-[var(--color-primary)] text-[#09090b] shadow-md shadow-[var(--color-primary)]/20"
                                                    : "text-[var(--color-text-label)] hover:text-[var(--color-text-value)] hover:bg-[var(--color-terciary)]"}
                                                `}
                                            >
                                                {option.label}
                                            </button>
                                        </li>
                                    ))
                                ) : (
                                    <li className="p-3 text-center text-[var(--color-text-sub)] text-[13px] font-medium">
                                        Nenhuma opção disponível
                                    </li>
                                )}
                            </ul>
                        </motion.div>
                    )}
                </AnimatePresence>
            </div>

            {desc && (
                <span className="text-[var(--color-text-sub)] text-[13px] ml-1 font-medium">
                    {desc}
                </span>
            )}
        </div>
    );
}