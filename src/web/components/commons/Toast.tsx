import React, { useState, useEffect } from 'react';

interface ToastProps {
    toast: {
        id: string;
        message: string;
        type: 'success' | 'error';
    };
    removeToast: (id: string) => void;
}

const Toast: React.FC<ToastProps> = ({ toast, removeToast }) => {
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        // Timer para iniciar a animação de "subida"
        const showTimer = setTimeout(() => setIsVisible(true), 10);

        // Timer para fechar automaticamente após 3 segundos
        const hideTimer = setTimeout(() => {
            setIsVisible(false);
            setTimeout(() => removeToast(toast.id), 300);
        }, 3000);

        return () => {
            clearTimeout(showTimer);
            clearTimeout(hideTimer);
        };
    }, [toast, removeToast]);

    return (
        <div
            className={`
                transform transition-all duration-300 ease-in-out
                ${isVisible ? 'translate-y-0 opacity-100 scale-100' : 'translate-y-8 opacity-0 scale-95'}
                flex items-center gap-3 px-4 py-3 rounded-lg shadow-2xl min-w-[320px] pointer-events-auto
                border border-(--color-terciary)
            `}
            style={{
                backgroundColor: 'var(--color-navbar)',
                borderLeft: `4px solid ${toast.type === 'error' ? 'var(--color-danger)' : 'var(--color-primary)'}`,
                color: 'var(--color-text-value)',
                boxShadow: 'var(--card-shadow)'
            }}
        >
            {/* Ícone customizado baseado no tipo (Ciano para Sucesso, Vermelho para Erro) */}
            {toast.type === 'error' ? (
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" className="text-(--color-danger)">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="2"/>
                    <path d="M12 8V12" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                    <circle cx="12" cy="16" r="1" fill="currentColor"/>
                </svg>
            ) : (
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" className="text-(--color-primary)">
                    <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M9 12L11 14L15 10" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
            )}

            <span className="text-[13px] font-semibold tracking-tight flex-1">
                {toast.message}
            </span>

            {/* Botão de fechar */}
            <button
                onClick={() => setIsVisible(false)}
                className="text-(--color-text-sub) hover:text-(--color-text-value) transition-colors p-1"
            >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    );
};

export default Toast;