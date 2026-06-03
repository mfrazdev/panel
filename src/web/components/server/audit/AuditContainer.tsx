import React, { useEffect, useState } from "react";
import { useServerContext } from "@/web/contexts/ServerContext";
import LoadingPage from "@/web/components/commons/LoadingPage";
import { useToast } from "@/web/contexts/ToastContext";
import { Activity, Clock, Globe, Monitor, Shield, ChevronLeft, ChevronRight } from "lucide-react";
import Card from "../../commons/components/Card"; // Importando o Card blindado
import Button from "../../commons/components/Button"; // Importando o botão

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
                const msgUint8 = new TextEncoder().encode(email.trim().toLowerCase());
                const hashBuffer = await crypto.subtle.digest("SHA-256", msgUint8);
                const hashArray = Array.from(new Uint8Array(hashBuffer));
                const hashHex = hashArray.map(b => b.toString(16).padStart(2, "0")).join("");
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

    // ===== ESTADOS DE PAGINAÇÃO =====
    // Pega o initial state direto da URL pra caso o usuário atualize a página (ex: ?page=0&limit=10)
    const [page, setPage] = useState(() => {
        const params = new URLSearchParams(window.location.search);
        return parseInt(params.get("page") || "0", 10);
    });

    const [limit, setLimit] = useState(() => {
        const params = new URLSearchParams(window.location.search);
        return parseInt(params.get("limit") || "10", 10);
    });

    const [totalRecords, setTotalRecords] = useState(0);

    const loadAudits = async () => {
        if (!server) return;
        setIsLoading(true);
        try {
            // Mandando a paginação na API tlgd
            const request = await sendApiRequest(`/audit?page=${page}&limit=${limit}`);
            const data = await request.json();

            if (!request.ok) {
                toast.addToast(data.error || "Erro ao carregar auditoria.", "error");
                return;
            }

            setAudits(Array.isArray(data.audits) ? data.audits : []);

            // O backend precisa retornar um total pra gente saber quantos itens tem no total
            // Se não retornar, usamos o length do array provisoriamente
            setTotalRecords(data.total || data.audits?.length || 0);
        } catch (error) {
            console.error("Erro ao carregar auditoria:", error);
            toast.addToast("Erro ao conectar com o servidor.", "error");
        } finally {
            setIsLoading(false);
        }
    };

    // Atualiza a busca e a URL na mesma tacada sempre que page, limit ou server mudarem
    useEffect(() => {
        if (!server) return;

        const url = new URL(window.location.href);
        url.searchParams.set("page", page.toString());
        url.searchParams.set("limit", limit.toString());
        window.history.pushState({}, "", url); // Muda a URL sem recarregar a página

        loadAudits();
    }, [server, page, limit]);

    if (isLoadingServer || (isLoading && audits.length === 0)) {
        return (
            <div className="min-h-screen flex justify-center items-center">
                <LoadingPage />
            </div>
        );
    }

    if (!server) return null;

    return (
        <main className="flex-1 flex flex-col py-6 px-4 md:py-8 md:px-10 xl:px-20 overflow-x-hidden animate-[fadeIn_0.4s_ease-out] gap-8">

            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div>
                    <h1 className="text-3xl font-black tracking-tight text-[var(--color-text-value)] mb-2 flex items-center gap-3">
                        <Activity className="w-8 h-8 text-[var(--color-primary)]" />
                        Logs de Auditoria
                    </h1>

                    <p className="text-[var(--color-text-sub)] text-sm font-medium">
                        Acompanhe o histórico de ações e eventos importantes deste servidor.
                    </p>
                </div>

                <Card>
                    <div className="text-[13px] font-medium text-[var(--color-text-sub)] -m-2">
                        Total de Registros:
                        <span className="text-[var(--color-text-value)] font-bold ml-1">
                {totalRecords}
            </span>
                    </div>
                </Card>
            </div>

            {/* Linha Divisória */}
            <div className="flex flex-col gap-6">
                <div className="flex items-center gap-4 mt-2">
                    <h3 className="text-[15px] font-bold text-[var(--color-text-value)] opacity-90 tracking-tight">Histórico de Eventos</h3>
                    <div className="h-[1px] flex-1 bg-gradient-to-r from-white/10 to-transparent"></div>
                </div>

                {audits.length === 0 && !isLoading ? (
                    <div className="flex flex-col items-center justify-center text-center py-20 rounded-2xl border-2 border-dashed border-white/5">
                        <div className="w-16 h-16 rounded-full bg-[var(--color-secondary)] border border-white/5 flex items-center justify-center text-[var(--color-text-sub)] mb-5 shadow-sm">
                            <Shield className="w-7 h-7" />
                        </div>
                        <div>
                            <p className="text-[16px] font-bold text-[var(--color-text-value)] tracking-tight">Nenhum registro encontrado</p>
                            <p className="text-[13px] font-medium text-[var(--color-text-sub)] mt-1.5 leading-relaxed max-w-sm mx-auto">
                                Não há eventos na página {page}.
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="flex flex-col gap-4">
                        {/* Renderizando o seu Card blindado pra cada log tlgd */}
                        {audits.map((audit) => (
                            <Card key={audit.id}>
                                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
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
                            </Card>
                        ))}
                    </div>
                )}

                {/* ===== CONTROLES DE PAGINAÇÃO ===== */}
                {audits.length > 0 && (
                    <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mt-4 bg-[var(--color-secondary)] p-4 rounded-2xl border border-white/5">
                        <div className="flex items-center gap-3">
                            <span className="text-[13px] font-medium text-[var(--color-text-sub)]">Exibir:</span>
                            <select
                                value={limit}
                                onChange={(e) => {
                                    setLimit(Number(e.target.value));
                                    setPage(0); // Volta pro começo quando muda o limite
                                }}
                                className="bg-[var(--color-terciary)] border border-white/5 text-[var(--color-text-value)] text-sm rounded-lg px-3 py-2 outline-none cursor-pointer focus:border-[var(--color-primary)]/50 transition-colors"
                            >
                                <option value={10}>10 por página</option>
                                <option value={25}>25 por página</option>
                                <option value={50}>50 por página</option>
                            </select>
                        </div>

                        <div className="flex items-center gap-3">
                            <Button
                                variant="secondary"
                                onClick={() => setPage(p => Math.max(0, p - 1))}
                                disabled={page === 0 || isLoading}
                                className="!px-4 !py-2"
                            >
                                <ChevronLeft className="w-4 h-4" />
                            </Button>

                            <span className="text-[13px] font-bold text-[var(--color-text-value)] px-2 bg-[var(--color-terciary)] py-1.5 rounded-lg border border-white/5">
                                Página {page}
                            </span>

                            <Button
                                variant="secondary"
                                onClick={() => setPage(p => p + 1)}
                                // Desabilita se a API retornar menos itens que o limit (significa que é a última página)
                                disabled={audits.length < limit || isLoading}
                                className="!px-4 !py-2"
                            >
                                <ChevronRight className="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </main>
    );
}