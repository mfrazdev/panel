import React from "react";

type CardProps = {
    children: React.ReactNode
    title?: string;
}

export default function Card({ children, title } : CardProps) {
    return (
        <div className="bg-(--color-secondary) rounded-md shadow-(--card-shadow)">
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