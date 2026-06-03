import React, { useEffect, useState } from "react";
import { useServerContext } from "@/web/contexts/ServerContext";
import LoadingPage from "@/web/components/commons/LoadingPage";
import Card from "@/web/components/commons/components/Card";
import Button from "@/web/components/commons/components/Button";
import { useToast } from "@/web/contexts/ToastContext";
import { Trash2, Copy, Network, Plug, Lock } from "lucide-react";

type AllocationItem = {
    id: number;
    nodeId?: string | null;
    ip: string;
    externalIp?: string | null;
    port: number;
};

type AdditionalAllocation = AllocationItem & {
    type: "FIXED" | "BYUSER";
};

export default function AllocationsContainer() {
    const { sendApiRequest, server, isLoadingServer, allocation } = useServerContext();
    const [isLoading, setIsLoading] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [deletingAlloc, setDeletingAlloc] = useState<number | null>(null);
    const [additionalAllocations, setAdditionalAllocations] = useState<AdditionalAllocation[]>([]);
    const toast = useToast();

    const loadAllocations = async () => {
        if (!server) return;
        setIsLoading(true);
        try {
            const request = await sendApiRequest("/allocations");
            const data = await request.json();

            if (!request.ok) {
                toast.addToast(data.error || "Erro ao carregar alocações.", "error");
                return;
            }

            setAdditionalAllocations(Array.isArray(data.additionalAllocations) ? data.additionalAllocations : []);
        } catch (error) {
            console.error("Erro ao carregar alocações adicionais:", error);
            toast.addToast("Erro ao conectar com o servidor.", "error");
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        if (!server) return;
        loadAllocations();
    }, [server]);

    // O uso de alocações é medido pelas alocações que o próprio usuário adicionou (BYUSER)
    const userAddedCount = additionalAllocations.filter(a => a.type === "BYUSER").length;
    const maxAllocations = server?.maxAdditionalAllocations;
    // Se maxAllocations for nulo ou vazio, é ilimitado
    const isLimitReached = maxAllocations !== null && maxAllocations !== undefined && userAddedCount >= maxAllocations;

    const handleAddAllocation = async () => {
        if (isLimitReached) {
            toast.addToast(`Limite de ${maxAllocations} porta(s) adicional(is) atingido.`, "error");
            return;
        }

        setIsSubmitting(true);
        try {
            const request = await sendApiRequest("/allocations/add", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({})
            });
            const data = await request.json();

            if (request.ok) {
                setAdditionalAllocations(Array.isArray(data.additionalAllocations) ? data.additionalAllocations : []);
                toast.addToast("Porta adicional adicionada.", "success");
            } else {
                toast.addToast(data.error || "Erro ao adicionar allocation.", "error");
            }
        } catch (error) {
            console.error("Erro ao adicionar allocation:", error);
            toast.addToast("Erro de conexão ao adicionar allocation.", "error");
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleRemoveAllocation = async (allocationId: number) => {
        if (!confirm(`Tem certeza que deseja liberar esta porta?`)) {
            return;
        }

        setDeletingAlloc(allocationId);
        try {
            const request = await sendApiRequest("/allocations/remove", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ allocationId })
            });
            const data = await request.json();

            if (request.ok) {
                setAdditionalAllocations(Array.isArray(data.additionalAllocations) ? data.additionalAllocations : []);
                toast.addToast("Porta removida.", "success");
            } else {
                toast.addToast(data.error || "Erro ao remover allocation.", "error");
            }
        } catch (error) {
            console.error("Erro ao remover allocation:", error);
            toast.addToast("Erro de conexão ao remover allocation.", "error");
        } finally {
            setDeletingAlloc(null);
        }
    };

    // ===== UTILITÁRIOS =====
    const copyToClipboard = (text: string, label: string) => {
        navigator.clipboard.writeText(text);
        toast.addToast(`${label} copiado para a área de transferência!`, "success");
    };

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

            {/* Header / Ações Principais (Estilo atualizado igual ao Admin) */}
            <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 className="text-3xl font-black tracking-tight text-[var(--color-text-value)] mb-2 flex items-center gap-3">
                        <Network className="w-8 h-8 text-[var(--color-primary)]" />
                        Alocações e Portas
                    </h1>
                    <p className="text-[var(--color-text-sub)] text-sm font-medium">
                        Gerencie os endereços de rede e as portas vinculadas a este servidor.
                    </p>
                </div>

                <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <Card>
                        <div className="text-[13px] font-medium text-[var(--color-text-sub)] -m-2">
                            Uso Adicional: <span className="text-[var(--color-text-value)] font-bold ml-1">{userAddedCount}</span> / {maxAllocations === null || maxAllocations === undefined ? 'Ilimitado' : maxAllocations}
                        </div>
                    </Card>
                    <Button
                        variant="info"
                        onClick={handleAddAllocation}
                        disabled={isSubmitting || isLimitReached}
                    >
                        {isSubmitting && !deletingAlloc ? 'Buscando...' : 'Nova Porta'}
                    </Button>
                </div>
            </div>

            {/* Alocação Primária */}
            <Card title="ALOCAÇÃO PRIMÁRIA (PADRÃO)">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label className="block text-[12px] font-bold text-[var(--color-text-sub)] mb-2 uppercase tracking-wider">
                            IP / Endpoint Principal
                        </label>
                        <div className="flex shadow-sm rounded-xl overflow-hidden border border-white/5">
                            <input
                                readOnly
                                value={allocation ? `${allocation.ip}:${allocation.port}` : "-"}
                                className="w-full bg-[var(--color-terciary)] text-[var(--color-text-value)] font-medium text-[14px] px-4 py-3 focus:outline-none"
                            />
                            <button
                                onClick={() => copyToClipboard(allocation ? `${allocation.ip}:${allocation.port}` : "", "Endpoint")}
                                className="bg-[var(--color-secondary)] hover:bg-white/5 border-l border-white/5 text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] px-4 transition-all duration-200 flex items-center justify-center cursor-pointer"
                                title="Copiar"
                            >
                                <Copy className="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div>
                        <label className="block text-[12px] font-bold text-[var(--color-text-sub)] mb-2 uppercase tracking-wider">
                            IP Externo Principal
                        </label>
                        <div className="flex shadow-sm rounded-xl overflow-hidden border border-white/5">
                            <input
                                readOnly
                                value={allocation?.externalIp ? `${allocation.externalIp}:${allocation.port}` : "-"}
                                className="w-full bg-[var(--color-terciary)] text-[var(--color-text-value)] font-medium text-[14px] px-4 py-3 focus:outline-none"
                            />
                            <button
                                onClick={() => copyToClipboard(allocation?.externalIp ? `${allocation.externalIp}:${allocation.port}` : "", "IP Externo")}
                                className="bg-[var(--color-secondary)] hover:bg-white/5 border-l border-white/5 text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] px-4 transition-all duration-200 flex items-center justify-center cursor-pointer"
                                title="Copiar"
                            >
                                <Copy className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </Card>

            {/* Lista de Alocações Adicionais */}
            <div className="flex flex-col gap-6">
                {/* Linha Divisória de Categoria igual ao Dashboard */}
                <div className="flex items-center gap-4 mt-2">
                    <h3 className="text-[15px] font-bold text-[var(--color-text-value)] opacity-90 tracking-tight">Portas Adicionais</h3>
                    <div className="h-[1px] flex-1 bg-gradient-to-r from-white/10 to-transparent"></div>
                </div>

                {additionalAllocations.length === 0 ? (
                    /* Empty State Limpo - Design Tracejado do Dashboard */
                    <div className="flex flex-col items-center justify-center text-center py-20 rounded-2xl border-2 border-dashed border-white/5">
                        <div className="w-16 h-16 rounded-full bg-[var(--color-secondary)] border border-white/5 flex items-center justify-center text-[var(--color-text-sub)] mb-5 shadow-sm">
                            <Plug className="w-7 h-7" />
                        </div>
                        <div>
                            <p className="text-[16px] font-bold text-[var(--color-text-value)] tracking-tight">Nenhuma porta adicional</p>
                            <p className="text-[13px] font-medium text-[var(--color-text-sub)] mt-1.5 leading-relaxed max-w-sm mx-auto">
                                As portas adicionais servem para plugins que requerem conexões extras. Adicione uma porta no botão acima.
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {additionalAllocations.map((alloc) => {
                            const isDeleting = deletingAlloc === alloc.id;
                            const endpoint = `${alloc.ip}:${alloc.port}`;
                            const externalEndpoint = alloc.externalIp ? `${alloc.externalIp}:${alloc.port}` : null;
                            const isFixed = alloc.type === "FIXED";

                            return (
                                <Card key={`${alloc.type}-${alloc.id}`} title={`PORTA: ${alloc.port}`}>
                                    <div className="space-y-5">

                                        {/* IP e Porta */}
                                        <div>
                                            <label className="block text-[12px] font-bold text-[var(--color-text-sub)] mb-2 uppercase tracking-wider">
                                                Endpoint
                                            </label>
                                            <div className="flex shadow-sm rounded-xl overflow-hidden border border-white/5">
                                                <input
                                                    readOnly
                                                    value={endpoint}
                                                    className="w-full bg-[var(--color-terciary)] text-[var(--color-text-value)] font-medium text-[14px] px-4 py-3 focus:outline-none"
                                                />
                                                <button
                                                    onClick={() => copyToClipboard(endpoint, "Endpoint")}
                                                    className="bg-[var(--color-secondary)] hover:bg-white/5 border-l border-white/5 text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] px-4 transition-all duration-200 flex items-center justify-center cursor-pointer"
                                                    title="Copiar"
                                                >
                                                    <Copy className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>

                                        {/* IP Externo se existir */}
                                        {externalEndpoint && (
                                            <div>
                                                <label className="block text-[12px] font-bold text-[var(--color-text-sub)] mb-2 uppercase tracking-wider">
                                                    IP Externo
                                                </label>
                                                <div className="flex shadow-sm rounded-xl overflow-hidden border border-white/5">
                                                    <input
                                                        readOnly
                                                        value={externalEndpoint}
                                                        className="w-full bg-[var(--color-terciary)] text-[var(--color-text-value)] font-medium text-[14px] px-4 py-3 focus:outline-none"
                                                    />
                                                    <button
                                                        onClick={() => copyToClipboard(externalEndpoint, "IP Externo")}
                                                        className="bg-[var(--color-secondary)] hover:bg-white/5 border-l border-white/5 text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] px-4 transition-all duration-200 flex items-center justify-center cursor-pointer"
                                                        title="Copiar"
                                                    >
                                                        <Copy className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        )}

                                        {/* Linha Divisória e Botão Deletar */}
                                        <div className="border-t border-white/5 pt-5 mt-5 flex justify-between items-center">
                                            <span className="text-[12px] text-[var(--color-text-sub)] font-medium flex items-center gap-1.5">
                                                Tipo:
                                                <span className={`px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider ${isFixed ? "bg-orange-500/10 text-orange-400" : "bg-[var(--color-primary)]/10 text-[var(--color-primary)]"}`}>
                                                    {isFixed ? "Vínculo Obrigatório" : "Adicionada por você"}
                                                </span>
                                            </span>

                                            {isFixed ? (
                                                <div className="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold bg-white/5 text-[var(--color-text-sub)] cursor-not-allowed border border-white/5" title="Alocações FIXAS só podem ser gerenciadas pelo Administrador.">
                                                    <Lock className="w-4 h-4" /> Bloqueado
                                                </div>
                                            ) : (
                                                <Button
                                                    variant="danger"
                                                    onClick={() => handleRemoveAllocation(alloc.id)}
                                                    disabled={isDeleting || isSubmitting}
                                                    className="!py-2 !px-4 !text-[13px]"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                    {isDeleting ? 'Removendo...' : 'Excluir'}
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>
        </main>
    );
}