import React, { useId, forwardRef } from "react";

type BaseProps = {
    label?: string | null;
    desc?: string | null;
    className?: string;
};

type InputProps =
    | (BaseProps & React.InputHTMLAttributes<HTMLInputElement> & { as?: "input" })
    | (BaseProps & React.TextareaHTMLAttributes<HTMLTextAreaElement> & { as: "textarea" });

const Input = forwardRef<
    HTMLInputElement | HTMLTextAreaElement,
    InputProps
>(function Input(
    {
        label,
        desc,
        className,
        as = "input",
        ...props
    },
    ref
) {
    const id = useId();

    const baseClass = `
        w-full bg-[var(--color-console)] text-[var(--color-text-value)] placeholder:text-[var(--color-text-sub)]/50
        px-4 py-3 rounded-[14px] border-none outline-none shadow-inner
        transition-colors duration-300
        resize-none
    `;

    return (
        <div className={`flex flex-col gap-1.5 w-full ${className || ""}`}>
            {label && (
                <label
                    htmlFor={id}
                    className="font-bold text-[var(--color-text-sub)] text-[12px] uppercase tracking-wider ml-1"
                >
                    {label}
                </label>
            )}

            {/* Glow Wrapper do Input: O focus-within ativa o gradiente primário na borda */}
            <div className="group relative p-[4px] rounded-2xl bg-gradient-to-br from-[var(--color-terciary)] via-[var(--color-secondary)] to-transparent hover:from-[var(--color-text-sub)]/30 focus-within:from-[var(--color-primary)] focus-within:via-[var(--color-primary)]/50 transition-all duration-500 ease-out shadow-sm focus-within:shadow-[var(--color-primary)]/10">
                {as === "textarea" ? (
                    <textarea
                        ref={ref as React.Ref<HTMLTextAreaElement>}
                        id={id}
                        className={baseClass}
                        {...(props as React.TextareaHTMLAttributes<HTMLTextAreaElement>)}
                    />
                ) : (
                    <input
                        ref={ref as React.Ref<HTMLInputElement>}
                        id={id}
                        className={baseClass}
                        {...(props as React.InputHTMLAttributes<HTMLInputElement>)}
                    />
                )}
            </div>

            {desc && (
                <span className="text-[var(--color-text-sub)] text-[13px] ml-1 font-medium">
                    {desc}
                </span>
            )}
        </div>
    );
});

export default Input;