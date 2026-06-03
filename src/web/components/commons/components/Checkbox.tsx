import React, { useId } from "react";
import { motion } from "framer-motion";

type CheckboxProps = {
    label?: string | null;
    desc?: string | null;
    checked: boolean;
    onChange: (checked: boolean) => void;
    className?: string;
};

export default function Checkbox({ label, desc, checked, onChange, className }: CheckboxProps) {
    const id = useId();

    return (
        <div
            className={`flex items-center gap-4 cursor-pointer backdrop-blur-md transition-all duration-300 group w-fit ${className || ""}`}
            onClick={() => onChange(!checked)}
        >
            {/* Glow Wrapper do Switch: Fica com glow primário quando checked e no hover */}
            <div className={`p-[4px] rounded-full transition-all duration-500 ease-out shadow-sm group-hover:shadow-md ${
                checked
                    ? 'bg-gradient-to-br from-[var(--color-primary)]/50 via-[var(--color-primary)]/20 to-transparent shadow-[var(--color-primary)]/20'
                    : 'bg-gradient-to-br from-[var(--color-terciary)] via-[var(--color-secondary)] to-transparent group-hover:from-[var(--color-primary)]/30'
            }`}>
                <motion.div
                    className="w-12 h-6 rounded-full relative shadow-inner flex-shrink-0 bg-[var(--color-terciary)] overflow-hidden border-none"
                    animate={{
                        backgroundColor: checked ? 'var(--color-primary)' : 'var(--color-terciary)'
                    }}
                    transition={{ duration: 0.3, ease: "easeInOut" }}
                >
                    <motion.div
                        className="absolute top-1 w-4 h-4 rounded-full bg-white shadow-md"
                        animate={{
                            x: checked ? 26 : 4
                        }}
                        transition={{ duration: 0.3, ease: "easeInOut" }}
                    />
                </motion.div>
            </div>

            {/* Labels */}
            {(label || desc) && (
                <div className="flex flex-col items-start select-none">
                    {label && (
                        <span className="text-[15px] font-light text-[var(--color-text-value)] tracking-widest uppercase transition-colors group-hover:text-[var(--color-primary)]">
                            {label}
                        </span>
                    )}
                    {desc && (
                        <span className={`text-[var(--color-text-sub)] text-[15px] font-light ${label ? 'mt-0.5' : ''}`}>
                            {desc}
                        </span>
                    )}
                </div>
            )}
        </div>
    );
}