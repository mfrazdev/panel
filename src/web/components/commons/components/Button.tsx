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

    const baseClass = `
        cursor-pointer
        w-full h-full
        relative flex items-center justify-center gap-2
        px-6 py-2.5 rounded-xl
        font-bold text-sm tracking-wide
        transition-all duration-300
        outline-none border-none
        disabled:opacity-50 disabled:cursor-not-allowed
        whitespace-nowrap
    `;

    // Wrapper limpo. Sem receber className externo para não bugar o padding de 1.5px da borda
    const wrapperBase = `
        group relative !p-[4px] rounded-2xl transition-all duration-500 ease-out shadow-sm hover:shadow-md hover:-translate-y-0.5 transform
        disabled:hover:translate-y-0 disabled:shadow-none
        ${fullWidth ? "flex w-full" : "inline-flex w-max"}
    `;

    const variants = {
        primary: {
            wrapper: "bg-gradient-to-br from-[var(--color-primary)]/60 via-transparent to-transparent hover:from-[var(--color-primary)] shadow-[var(--color-primary)]/20",
            button: "bg-[var(--color-primary)] text-white hover:brightness-110 active:brightness-95",
        },
        secondary: {
            wrapper: "bg-gradient-to-br from-[var(--color-terciary)] via-[var(--color-secondary)] to-transparent hover:from-[var(--color-text-label)]/50",
            button: "bg-[var(--color-secondary)] text-[var(--color-text-value)] hover:bg-[var(--color-terciary)] active:brightness-95",
        },
        ghost: {
            wrapper: "bg-transparent shadow-none hover:shadow-none hover:-translate-y-0 !p-0",
            button: "bg-transparent text-[var(--color-text-label)] hover:text-[var(--color-text-value)] hover:bg-[var(--color-secondary)]",
        },
        danger: {
            wrapper: "bg-gradient-to-br from-[var(--color-danger)]/60 via-transparent to-transparent hover:from-[var(--color-danger)] shadow-[var(--color-danger)]/20",
            button: "bg-[var(--color-danger)] text-white hover:brightness-110 active:brightness-95",
        },
        success: {
            wrapper: "bg-gradient-to-br from-[var(--color-success)]/60 via-transparent to-transparent hover:from-[var(--color-success)] shadow-[var(--color-success)]/20",
            button: "bg-[var(--color-success)] text-white hover:brightness-110 active:brightness-95",
        },
        warning: {
            wrapper: "bg-gradient-to-br from-[var(--color-warning)]/60 via-transparent to-transparent hover:from-[var(--color-warning)] shadow-[var(--color-warning)]/20",
            button: "bg-[var(--color-warning)] text-[var(--color-background)] hover:brightness-110 active:brightness-95",
        },
        info: {
            wrapper: "bg-gradient-to-br from-[var(--color-info)]/60 via-transparent to-transparent hover:from-[var(--color-info)] shadow-[var(--color-info)]/20",
            button: "bg-[var(--color-info)] text-white hover:brightness-110 active:brightness-95",
        },
    };

    const variantData = variants[variant] || variants.primary;

    return (
        <div className={`${wrapperBase} ${variantData.wrapper}`}>
            <button
                className={`${baseClass} ${variantData.button} ${className || ""}`}
                {...props}
            />
        </div>
    );
}