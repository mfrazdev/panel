import React from "react";

type CardProps = {
    children: React.ReactNode
    title?: string;
    className?: string;
}

export default function Card({ children, title, className } : CardProps) {
    return (
        <div className={`bg-(--color-secondary) rounded-md shadow-(--card-shadow) ${className}`}>
            {title && (
                <div className="bg-(--color-sidebar) p-4 w-full rounded-t-md font-light text-(--color-text-label)">
                {title}
            </div>
            )}

            <div className="p-4 text-(--color-text-label)">
                {children}
            </div>
        </div>
    )
}