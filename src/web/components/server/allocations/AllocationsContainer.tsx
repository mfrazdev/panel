import React, { useEffect, useState } from "react";
import { useServerContext } from "@/web/contexts/ServerContext";
import LoadingPage from "@/web/components/commons/LoadingPage";
import Card from "@/web/components/commons/components/Card";
import Button from "@/web/components/commons/components/Button";
import { useToast } from "@/web/contexts/ToastContext";
// Trocamos o Database pelo Network e Plug para se adequar a Portas/Rede
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
        <main className="flex-1 flex flex-col p-6 md:p-8 overflow-x-hidden gap-8">
            {/* Header / Ações Principais */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-white flex items-center gap-2">
                        <Network className="w-6 h-6 text-blue-500" />
                        Alocações e Portas
                    </h1>
                    <p className="text-gray-400 mt-1">
                        Gerencie os endereços de rede e as portas vinculadas a este servidor.
                    </p>
                </div>

                <div className="flex items-center gap-4">
                    <div className="text-sm text-gray-300 bg-gray-800/50 px-4 py-2 rounded-lg border border-gray-700">
                        Uso Adicional: <span className="text-white font-semibold">{userAddedCount}</span> / {maxAllocations === null || maxAllocations === undefined ? 'Ilimitado' : maxAllocations}
                    </div>

                    <Button
                        variant="primary"
                        onClick={handleAddAllocation}
                        disabled={isSubmitting || isLimitReached}
                        className={`px-4 py-2 rounded-lg font-medium transition-all flex items-center gap-2
                            ${isSubmitting || isLimitReached 
                                ? 'bg-gray-600 text-gray-400 cursor-not-allowed' 
                                : 'bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-500/20'}`}
                    >
                        {isSubmitting && !deletingAlloc ? 'Buscando...' : 'Nova Porta'}
                    </Button>
                </div>
            </div>

            {/* Alocação Primária */}
            <Card title="ALOCAÇÃO PRIMÁRIA (PADRÃO)">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wider">IP / Endpoint Principal</label>
                        <div className="flex">
                            <input 
                                readOnly 
                                value={allocation ? `${allocation.ip}:${allocation.port}` : "-"} 
                                className="w-full bg-(--color-terciary) text-(--color-text-label) text-sm rounded-l-md px-3 py-2 focus:outline-none shadow-(--card-shadow)"
                            />
                            <button 
                                onClick={() => copyToClipboard(allocation ? `${allocation.ip}:${allocation.port}` : "", "Endpoint")}
                                className="bg-(--color-primary) hover:bg-(--color-primary)/50 text-(--color-text-label) px-3 rounded-r-md transition-colors"
                                title="Copiar"
                            >
                                <Copy className="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wider">IP Externo Principal</label>
                        <div className="flex">
                            <input 
                                readOnly 
                                value={allocation?.externalIp ? `${allocation.externalIp}:${allocation.port}` : "-"} 
                                className="w-full bg-(--color-terciary) text-(--color-text-label) text-sm rounded-l-md px-3 py-2 focus:outline-none shadow-(--card-shadow)"
                            />
                            <button 
                                onClick={() => copyToClipboard(allocation?.externalIp ? `${allocation.externalIp}:${allocation.port}` : "", "IP Externo")}
                                className="bg-(--color-primary) hover:bg-(--color-primary)/50 text-(--color-text-label) px-3 rounded-r-md transition-colors"
                                title="Copiar"
                            >
                                <Copy className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </Card>

            {/* Lista de Alocações Adicionais */}
            <div>
                <h2 className="text-xl font-semibold text-white mb-4">Portas Adicionais</h2>

                {additionalAllocations.length === 0 ? (
                    <div className="flex flex-col items-center justify-center p-12 bg-(--color-secondary) rounded-md border-dashed shadown-(--card-shadow)">
                        <Plug className="w-12 h-12 text-gray-600 mb-4" />
                        <h3 className="text-lg font-medium text-white mb-2">Nenhuma porta adicional</h3>
                        <p className="text-gray-400 text-center max-w-sm">
                            As portas adicionais servem para plugins que requerem conexões extras. Adicione uma porta no botão acima.
                        </p>
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
                                    <div className="space-y-4">
                                        
                                        {/* IP e Porta */}
                                        <div>
                                            <label className="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wider">Endpoint</label>
                                            <div className="flex">
                                                <input 
                                                    readOnly 
                                                    value={endpoint} 
                                                    className="w-full bg-(--color-terciary) text-(--color-text-label) text-sm rounded-l-md px-3 py-2 focus:outline-none shadow-(--card-shadow)"
                                                />
                                                <button 
                                                    onClick={() => copyToClipboard(endpoint, "Endpoint")}
                                                    className="bg-(--color-primary) hover:bg-(--color-primary)/50 text-(--color-text-label) px-3 rounded-r-md transition-colors"
                                                    title="Copiar"
                                                >
                                                    <Copy className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>

                                        {/* IP Externo se existir */}
                                        {externalEndpoint && (
                                            <div>
                                                <label className="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wider">IP Externo</label>
                                                <div className="flex">
                                                    <input 
                                                        readOnly 
                                                        value={externalEndpoint} 
                                                        className="w-full bg-(--color-terciary) text-(--color-text-label) text-sm rounded-l-md px-3 py-2 focus:outline-none shadow-(--card-shadow)"
                                                    />
                                                    <button 
                                                        onClick={() => copyToClipboard(externalEndpoint, "IP Externo")}
                                                        className="bg-(--color-primary) hover:bg-(--color-primary)/50 text-(--color-text-label) px-3 rounded-r-md transition-colors"
                                                        title="Copiar"
                                                    >
                                                        <Copy className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        )}
                                        
                                        {/* Linha Divisória e Botão Deletar */}
                                        <div className="border-t border-gray-800 pt-4 mt-4 flex justify-between items-center">
                                            <span className="text-xs text-(--color-text-label) flex items-center gap-1">
                                                Tipo: <strong className={isFixed ? "text-orange-400" : "text-blue-400"}>
                                                    {isFixed ? "Vínculo Obrigatório" : "Adicionada por você"}
                                                </strong>
                                            </span>

                                            {isFixed ? (
                                                <div className="flex items-center gap-2 px-3 py-1.5 rounded text-sm font-medium bg-gray-800/50 text-gray-500 cursor-not-allowed border border-gray-800" title="Alocações FIXAS só podem ser gerenciadas pelo Administrador.">
                                                    <Lock className="w-4 h-4" /> Bloqueado
                                                </div>
                                            ) : (
                                                <Button
                                                    variant="danger"
                                                    onClick={() => handleRemoveAllocation(alloc.id)}
                                                    disabled={isDeleting || isSubmitting}
                                                    className={`flex items-center gap-2 px-3 py-1.5`}
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