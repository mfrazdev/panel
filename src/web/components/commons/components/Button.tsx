import React from "react";

type ButtonProps = React.ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?:
        | "primary"
        | "secondary"
        | "ghost"
        | "danger"
        | "success"
        | "warning"
        | "info";
    fullWidth?: boolean;
};

export default function Button({
                                   variant = "primary",
                                   fullWidth = false,
                                   className,
                                   ...props
                               }: ButtonProps) {
    // Adicionado rounded-xl, font-bold, px-6, duration-300 e o hover:-translate-y-0.5
    const baseClass = `
        cursor-pointer
        inline-flex items-center justify-center gap-2
        px-6 py-3 rounded-xl
        font-bold text-sm tracking-wide
        transition-all duration-300 transform hover:-translate-y-0.5
        outline-none border-none shadow-md
        focus:ring-2 focus:ring-[var(--color-primary)]
        disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0
        ${fullWidth ? "w-full" : ""}
    `;

    const variants = {
        primary: `
            bg-[var(--color-primary)] text-[#09090b]
            hover:brightness-110 active:brightness-95 active:translate-y-0
        `,
        secondary: `
            bg-[var(--color-secondary)] text-[var(--color-text-label)]
            hover:brightness-110 active:brightness-95 active:translate-y-0
        `,
        ghost: `
            bg-transparent text-[var(--color-text-label)] shadow-none
            hover:bg-white/5 active:bg-white/10 hover:-translate-y-0 active:translate-y-0
        `,
        danger: `
            bg-[var(--color-danger)] text-white
            hover:brightness-110 active:brightness-95 active:translate-y-0
        `,
        success: `
            bg-[var(--color-success)] text-white
            hover:brightness-110 active:brightness-95 active:translate-y-0
        `,
        warning: `
            bg-[var(--color-warning)] text-[#09090b]
            hover:brightness-110 active:brightness-95 active:translate-y-0
        `,
        info: `
            bg-[var(--color-info)] text-white
            hover:brightness-110 active:brightness-95 active:translate-y-0
        `,
    };

    return (
        <button
            className={`
                ${baseClass}
                ${variants[variant]}
                ${className || ""}
            `}
            {...props}
        />
    );
}