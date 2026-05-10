import React, { useState, useId, useRef, useEffect } from "react";
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
                <label className="font-medium text-(--color-text-label) text-[11px] uppercase tracking-widest ml-1 opacity-80">
                    {label}
                </label>
            )}

            {/* Container Relativo apenas para o Botão e o Menu */}
            <div className="relative w-full">
                {/* Trigger (O "Botão" do Select) */}
                <button
                    type="button"
                    onClick={() => setIsOpen(!isOpen)}
                    className={`
                        w-full bg-(--color-terciary) text-(--color-text-label) 
                        p-4 rounded-xl border border-transparent outline-none flex items-center justify-between
                        focus:ring-2 focus:ring-(--color-primary) focus:border-transparent
                        transition-all duration-200 cursor-pointer 
                        hover:bg-(--color-terciary)/80 active:scale-[0.99]
                        ${className || ""}
                    `}
                >
                    <span className={`text-[14px] font-medium ${!selectedOption ? "text-(--color-text-sub)" : ""}`}>
                        {selectedOption ? selectedOption.label : placeholder}
                    </span>
                    <motion.div
                        animate={{ rotate: isOpen ? 180 : 0 }}
                        transition={{ duration: 0.2 }}
                        className="flex items-center"
                    >
                        <ChevronDown size={18} className="text-(--color-text-sub)" />
                    </motion.div>
                </button>

                {/* Dropdown Menu - Agora posicionado em relação ao botão */}
                <AnimatePresence>
                    {isOpen && (
                        <motion.ul
                            initial={{ opacity: 0, y: -10, scale: 0.98 }}
                            animate={{ opacity: 1, y: 5, scale: 1 }}
                            exit={{ opacity: 0, y: -10, scale: 0.98 }}
                            transition={{ duration: 0.15, ease: "easeOut" }}
                            className="absolute left-0 w-full bg-(--color-terciary) rounded-xl overflow-hidden z-50 shadow-2xl p-1.5 border border-(--color-secondary) max-h-[250px] overflow-y-auto"
                        >
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
                                                cursor-pointer w-full text-left p-3 rounded-lg text-[14px] font-medium transition-all
                                                ${value === option.value
                                                ? "bg-(--color-primary) text-white"
                                                : "text-(--color-text-label) hover:bg-(--color-secondary)"}
                                            `}
                                        >
                                            {option.label}
                                        </button>
                                    </li>
                                ))
                            ) : (
                                <li className="p-3 text-center text-(--color-text-sub) text-[13px]">
                                    Nenhuma opção disponível
                                </li>
                            )}
                        </motion.ul>
                    )}
                </AnimatePresence>
            </div>

            {desc && (
                <span className="text-(--color-text-sub) text-[13px] ml-1 font-normal leading-relaxed opacity-70">
                    {desc}
                </span>
            )}
        </div>
    );
}