import React, { useEffect, useState } from "react";
import { useServerContext } from "@/web/contexts/ServerContext";
import LoadingPage from "@/web/components/commons/LoadingPage";
import { useToast } from "@/web/contexts/ToastContext";
import { Activity, Clock, Globe, Monitor, Shield } from "lucide-react";

// ===== TIPAGENS =====
type AuditUser = {
    id: number;
    email: string;
    first_name: string;
    last_name: string;
};

type AuditLog = {
    id: number;
    server_id: number;
    user_id: number;
    action: string;
    ip: string;
    userAgent: string;
    created_at: string;
    updated_at: string;
    user: AuditUser;
};

// ===== SUB-COMPONENTE: AVATAR GRAVATAR =====
const UserAvatar = ({ email, name }: { email: string; name: string }) => {
    const [avatarUrl, setAvatarUrl] = useState<string>("");

    useEffect(() => {
        const fetchGravatar = async () => {
            try {
                // Gravatar suporta SHA-256, usando a API nativa do browser para não precisar de lib MD5
                const msgUint8 = new TextEncoder().encode(email.trim().toLowerCase());
                const hashBuffer = await crypto.subtle.digest("SHA-256", msgUint8);
                const hashArray = Array.from(new Uint8Array(hashBuffer));
                const hashHex = hashArray.map(b => b.toString(16).padStart(2, "0")).join("");

                // ?d=mp coloca aquele bonequinho misterioso padrão caso não tenha foto
                setAvatarUrl(`https://www.gravatar.com/avatar/${hashHex}?d=mp`);
            } catch (error) {
                setAvatarUrl("https://www.gravatar.com/avatar/?d=mp");
            }
        };
        fetchGravatar();
    }, [email]);

    return (
        <img
            src={avatarUrl}
            alt={`Avatar de ${name}`}
            className="w-10 h-10 rounded-full shadow-sm border border-white/5 bg-[var(--color-terciary)]"
        />
    );
};

export default function AuditContainer() {
    const { sendApiRequest, server, isLoadingServer } = useServerContext();
    const [audits, setAudits] = useState<AuditLog[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const toast = useToast();

    const loadAudits = async () => {
        if (!server) return;
        setIsLoading(true);
        try {
            const request = await sendApiRequest("/audit");
            const data = await request.json();

            if (!request.ok) {
                toast.addToast(data.error || "Erro ao carregar auditoria.", "error");
                return;
            }

            setAudits(Array.isArray(data.audits) ? data.audits : []);
        } catch (error) {
            console.error("Erro ao carregar auditoria:", error);
            toast.addToast("Erro ao conectar com o servidor.", "error");
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        if (!server) return;
        loadAudits();
    }, [server]);

    if (isLoadingServer || isLoading) {
        return (
            <div className="min-h-screen flex justify-center items-center">
                <LoadingPage />
            </div>
        );
    }

    if (!server) return null;

    return (
        <main className="flex-1 flex flex-col py-6 px-4 md:py-8 md:px-10 xl:px-20 overflow-x-hidden animate-[fadeIn_0.4s_ease-out] gap-8">

            {/* Header (Mesmo estilo clean) */}
            <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 className="text-3xl font-black tracking-tight text-[var(--color-text-value)] mb-2 flex items-center gap-3">
                        <Activity className="w-8 h-8 text-[var(--color-primary)]" />
                        Logs de Auditoria
                    </h1>
                    <p className="text-[var(--color-text-sub)] text-sm font-medium">
                        Acompanhe o histórico de ações e eventos importantes deste servidor.
                    </p>
                </div>

                <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div className="text-[13px] font-medium text-[var(--color-text-sub)] bg-[var(--color-secondary)] border border-white/5 px-5 py-3 rounded-xl shadow-sm">
                        Total de Registros: <span className="text-[var(--color-text-value)] font-bold ml-1">{audits.length}</span>
                    </div>
                </div>
            </div>

            {/* Linha Divisória */}
            <div className="flex flex-col gap-6">
                <div className="flex items-center gap-4 mt-2">
                    <h3 className="text-[15px] font-bold text-[var(--color-text-value)] opacity-90 tracking-tight">Histórico de Eventos</h3>
                    <div className="h-[1px] flex-1 bg-gradient-to-r from-white/10 to-transparent"></div>
                </div>

                {audits.length === 0 ? (
                    /* Empty State - Tracejado igual o de alocações */
                    <div className="flex flex-col items-center justify-center text-center py-20 rounded-2xl border-2 border-dashed border-white/5">
                        <div className="w-16 h-16 rounded-full bg-[var(--color-secondary)] border border-white/5 flex items-center justify-center text-[var(--color-text-sub)] mb-5 shadow-sm">
                            <Shield className="w-7 h-7" />
                        </div>
                        <div>
                            <p className="text-[16px] font-bold text-[var(--color-text-value)] tracking-tight">Nenhum registro encontrado</p>
                            <p className="text-[13px] font-medium text-[var(--color-text-sub)] mt-1.5 leading-relaxed max-w-sm mx-auto">
                                Este servidor ainda não possui eventos registrados na auditoria.
                            </p>
                        </div>
                    </div>
                ) : (
                    /* Lista de Logs - Estilo glass sem bordas pesadas */
                    <div className="flex flex-col gap-4">
                        {audits.map((audit) => (
                            <div
                                key={audit.id}
                                className="bg-[var(--color-secondary)] border border-white/5 shadow-sm rounded-xl p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-5 transition-all duration-200 hover:bg-white/[0.02]"
                            >
                                {/* Info do Usuário e Ação */}
                                <div className="flex items-center gap-4 flex-1">
                                    <UserAvatar email={audit.user.email} name={audit.user.first_name} />

                                    <div className="flex flex-col">
                                        <span className="text-[15px] font-bold text-[var(--color-text-value)] tracking-tight">
                                            {audit.action}
                                        </span>
                                        <span className="text-[13px] font-medium text-[var(--color-text-sub)]">
                                            por <strong className="text-[var(--color-text-value)] font-semibold">{audit.user.first_name} {audit.user.last_name}</strong> ({audit.user.email})
                                        </span>
                                    </div>
                                </div>

                                {/* Meta Infos (IP, Data, Browser) */}
                                <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 flex-wrap lg:justify-end">
                                    <div className="flex items-center gap-2 text-[12px] font-medium text-[var(--color-text-sub)] bg-[var(--color-terciary)] border border-white/5 px-3 py-1.5 rounded-lg shadow-sm" title="Endereço IP">
                                        <Globe className="w-3.5 h-3.5 text-[var(--color-primary)]" />
                                        {audit.ip}
                                    </div>

                                    <div className="flex items-center gap-2 text-[12px] font-medium text-[var(--color-text-sub)] bg-[var(--color-terciary)] border border-white/5 px-3 py-1.5 rounded-lg shadow-sm" title="Data do evento">
                                        <Clock className="w-3.5 h-3.5 text-[var(--color-primary)]" />
                                        {new Date(audit.created_at).toLocaleString("pt-BR", {
                                            day: '2-digit', month: '2-digit', year: 'numeric',
                                            hour: '2-digit', minute: '2-digit'
                                        })}
                                    </div>

                                    <div className="flex items-center gap-2 text-[12px] font-medium text-[var(--color-text-sub)] bg-[var(--color-terciary)] border border-white/5 px-3 py-1.5 rounded-lg shadow-sm max-w-[150px] md:max-w-[200px] truncate" title={audit.userAgent}>
                                        <Monitor className="w-3.5 h-3.5 text-[var(--color-primary)] shrink-0" />
                                        <span className="truncate">{audit.userAgent.split(' ')[0]}</span>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </main>
    );
}