import React, { useEffect, useState } from "react";
import { useServerContext } from "@/web/contexts/ServerContext";
import LoadingPage from "@/web/components/commons/LoadingPage";
import Card from "../../commons/components/Card";
import Input from "@/web/components/commons/components/Input";
import { useToast } from "@/web/contexts/ToastContext";
// Ícones
import { Trash2, Copy, Eye, EyeOff, Database } from "lucide-react";
import Button from "../../commons/components/Button";

export default function DatabasesContainer() {
    const { sendApiRequest, server, isLoadingServer } = useServerContext();
    const [databases, setDatabases] = useState<any[]>([]);
    const [maxDatabases, setMaxDatabases] = useState<number | null>(null);
    const [isLoadingDatabases, setIsLoadingDatabases] = useState(true);
    const [isCreating, setIsCreating] = useState(false);
    const [deletingDb, setDeletingDb] = useState<string | null>(null);
    const [visiblePasswords, setVisiblePasswords] = useState<Record<string, boolean>>({});

    const toast = useToast();

    // Busca a lista de bancos de dados ao carregar a página
    useEffect(() => {
        if (!server) return;

        async function fetchDatabases() {
            try {
                const request = await sendApiRequest("/databases");
                const data = await request.json();

                if (request.ok) {
                    setDatabases(data.databases || []);
                    setMaxDatabases(data.maxDatabases);
                } else {
                    toast.addToast(data.error || "Erro ao carregar bancos de dados.", "error");
                }
            } catch (error) {
                console.error("Erro ao buscar databases:", error);
                toast.addToast("Erro ao conectar com o servidor.", "error");
            } finally {
                setIsLoadingDatabases(false);
            }
        }

        fetchDatabases();
    }, [server]);

    // ===== CRIAR NOVO BANCO DE DADOS =====
    const handleCreateDatabase = async () => {
        if (maxDatabases !== null && databases.length >= maxDatabases) {
            toast.addToast(`Limite de ${maxDatabases} banco(s) de dados atingido.`, "error");
            return;
        }

        setIsCreating(true);
        try {
            const request = await sendApiRequest("/databases/create", {
                method: "POST",
                headers: { "Content-Type": "application/json" }
            });
            const data = await request.json();

            if (request.ok && data.success) {
                setDatabases(prev => [...prev, data.database]);
                toast.addToast("Banco de dados criado com sucesso! Salve a senha.", "success");
            } else {
                toast.addToast(data.error || "Erro ao criar banco de dados.", "error");
            }
        } catch (error) {
            console.error("Erro ao criar database:", error);
            toast.addToast("Erro de conexão ao criar banco de dados.", "error");
        } finally {
            setIsCreating(false);
        }
    };

    // ===== DELETAR BANCO DE DADOS =====
    const handleDeleteDatabase = async (dbName: string) => {
        if (!confirm(`Tem certeza que deseja deletar o banco de dados ${dbName}? Esta ação é irreversível e todos os dados serão perdidos.`)) {
            return;
        }

        setDeletingDb(dbName);
        try {
            const request = await sendApiRequest("/databases/remove", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ dbName })
            });
            const data = await request.json();

            if (request.ok && data.success) {
                setDatabases(prev => prev.filter(db => db.dbName !== dbName));
                toast.addToast("Banco de dados deletado com sucesso.", "success");
            } else {
                toast.addToast(data.error || "Erro ao deletar banco de dados.", "error");
            }
        } catch (error) {
            console.error("Erro ao deletar database:", error);
            toast.addToast("Erro de conexão ao deletar banco de dados.", "error");
        } finally {
            setDeletingDb(null);
        }
    };

    // ===== UTILITÁRIOS =====
    const copyToClipboard = (text: string, label: string) => {
        navigator.clipboard.writeText(text);
        toast.addToast(`${label} copiado para a área de transferência!`, "success");
    };

    const togglePasswordVisibility = (dbName: string) => {
        setVisiblePasswords(prev => ({
            ...prev,
            [dbName]: !prev[dbName]
        }));
    };

    if (isLoadingServer || isLoadingDatabases) {
        return <div className="min-h-screen flex justify-center items-center"><LoadingPage /></div>;
    }

    if (!server) return null;

    const isLimitReached = maxDatabases !== null && databases.length >= maxDatabases;

    return (
        <main className="flex-1 flex flex-col py-6 px-4 md:py-8 md:px-10 xl:px-20 overflow-x-hidden animate-[fadeIn_0.4s_ease-out] gap-8">

            {/* Header / Ações Principais no novo padrão */}
            <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 className="text-3xl font-black tracking-tight text-[var(--color-text-value)] mb-2 flex items-center gap-3">
                        <Database className="w-8 h-8 text-[var(--color-primary)]" />
                        Bancos de Dados
                    </h1>
                    <p className="text-[var(--color-text-sub)] text-sm font-medium">
                        Gerencie os bancos de dados MySQL vinculados a este servidor.
                    </p>
                </div>

                <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <Card>
                        <div className="text-[13px] font-medium text-[var(--color-text-sub)] -m-2">
                            Uso: <span className="text-[var(--color-text-value)] font-bold ml-1">{databases.length}</span> / {maxDatabases === null ? 'Ilimitado' : maxDatabases}
                        </div>
                    </Card>

                    <Button
                        variant="info"
                        onClick={handleCreateDatabase}
                        disabled={isCreating || isLimitReached}
                    >
                        {isCreating ? 'Criando...' : 'Novo Banco'}
                    </Button>
                </div>
            </div>

            {/* Lista de Bancos de Dados */}
            {databases.length === 0 ? (
                /* Empty State Tracejado */
                <div className="flex flex-col items-center justify-center text-center py-20 rounded-2xl border-2 border-dashed border-white/5">
                    <div className="w-16 h-16 rounded-full bg-[var(--color-secondary)] border border-white/5 flex items-center justify-center text-[var(--color-text-sub)] mb-5 shadow-sm">
                        <Database className="w-7 h-7" />
                    </div>
                    <div>
                        <p className="text-[16px] font-bold text-[var(--color-text-value)] tracking-tight">Nenhum banco de dados</p>
                        <p className="text-[13px] font-medium text-[var(--color-text-sub)] mt-1.5 leading-relaxed max-w-sm mx-auto">
                            Este servidor ainda não possui nenhum banco de dados MySQL criado. Clique no botão acima para provisionar um.
                        </p>
                    </div>
                </div>
            ) : (
                <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    {databases.map((db) => {
                        const showPassword = visiblePasswords[db.dbName] || false;
                        const isDeleting = deletingDb === db.dbName;
                        const endpoint = `${db.hostIp}:${db.hostPort}`;

                        return (
                            <Card key={db.dbName} title={`DATABASE: ${db.dbName}`}>
                                <div className="space-y-5">

                                    {/* Endpoint de Conexão */}
                                    <div>
                                        <label className="block text-[12px] font-bold text-[var(--color-text-sub)] mb-2 uppercase tracking-wider">
                                            Host / Endpoint
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

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        {/* Usuário */}
                                        <div>
                                            <label className="block text-[12px] font-bold text-[var(--color-text-sub)] mb-2 uppercase tracking-wider">
                                                Usuário
                                            </label>
                                            <div className="flex shadow-sm rounded-xl overflow-hidden border border-white/5">
                                                <input
                                                    readOnly
                                                    value={db.dbUser}
                                                    className="w-full bg-[var(--color-terciary)] text-[var(--color-text-value)] font-medium text-[14px] px-4 py-3 focus:outline-none"
                                                />
                                                <button
                                                    onClick={() => copyToClipboard(db.dbUser, "Usuário")}
                                                    className="bg-[var(--color-secondary)] hover:bg-white/5 border-l border-white/5 text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] px-4 transition-all duration-200 flex items-center justify-center cursor-pointer"
                                                    title="Copiar"
                                                >
                                                    <Copy className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>

                                        {/* Senha */}
                                        <div>
                                            <label className="block text-[12px] font-bold text-[var(--color-text-sub)] mb-2 uppercase tracking-wider">
                                                Senha
                                            </label>
                                            <div className="flex shadow-sm rounded-xl overflow-hidden border border-white/5">
                                                <input
                                                    readOnly
                                                    type={showPassword ? "text" : "password"}
                                                    value={db.password}
                                                    className="w-full bg-[var(--color-terciary)] text-[var(--color-text-value)] font-medium text-[14px] px-4 py-3 focus:outline-none"
                                                />
                                                <button
                                                    onClick={() => togglePasswordVisibility(db.dbName)}
                                                    className="bg-[var(--color-secondary)] hover:bg-white/5 border-l border-white/5 text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] px-4 transition-all duration-200 flex items-center justify-center cursor-pointer"
                                                    title="Mostrar/Ocultar"
                                                >
                                                    {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                                                </button>
                                                <button
                                                    onClick={() => copyToClipboard(db.password, "Senha")}
                                                    className="bg-[var(--color-secondary)] hover:bg-white/5 border-l border-white/5 text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] px-4 transition-all duration-200 flex items-center justify-center cursor-pointer"
                                                    title="Copiar"
                                                >
                                                    <Copy className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Linha Divisória e Botão Deletar */}
                                    <div className="border-t border-white/5 pt-5 mt-5 flex justify-between items-center">
                                        <span className="text-[12px] text-[var(--color-text-sub)] font-medium flex items-center gap-1.5">
                                            Criado em: {new Date(db.createdAt).toLocaleDateString('pt-BR')}
                                        </span>
                                        <Button
                                            variant="danger"
                                            onClick={() => handleDeleteDatabase(db.dbName)}
                                            disabled={isDeleting}
                                            className="!py-2 !px-4 !text-[13px]"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                            {isDeleting ? 'Deletando...' : 'Excluir Banco'}
                                        </Button>
                                    </div>
                                </div>
                            </Card>
                        );
                    })}
                </div>
            )}
        </main>
    );
}