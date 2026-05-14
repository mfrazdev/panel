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
        w-full bg-[var(--color-terciary)] text-[var(--color-text-value)] placeholder:text-[var(--color-text-sub)]
        p-4 rounded-xl border-none outline-none shadow-sm
        focus:ring-2 focus:ring-[var(--color-primary)] transition-all duration-200 
        resize-none
        ${className || ""}
    `;

    return (
        <div className="flex flex-col gap-1.5 w-full">
            {label && (
                <label
                    htmlFor={id}
                    className="font-bold text-[var(--color-text-sub)] text-[12px] uppercase tracking-wider ml-1"
                >
                    {label}
                </label>
            )}

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

            {desc && (
                <span className="text-[var(--color-text-sub)] text-[13px] ml-1 font-medium">
                    {desc}
                </span>
            )}
        </div>
    );
});

export default Input;