import React, { useEffect, useState } from "react";
import { useServerContext } from "@/web/contexts/ServerContext";
import LoadingPage from "@/web/components/commons/LoadingPage";
import Card from "../../commons/components/Card";
import Input from "@/web/components/commons/components/Input";
import { useToast } from "@/web/contexts/ToastContext";
// Ícones (ajuste a importação dependendo da biblioteca que você usa, ex: lucide-react ou heroicons)
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
        <main className="flex-1 flex flex-col p-6 md:p-8 overflow-x-hidden gap-8">
            {/* Header / Ações Principais */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-white flex items-center gap-2">
                        <Database className="w-6 h-6 text-(--color-info)" />
                        Bancos de Dados
                    </h1>
                    <p className="text-gray-400 mt-1">
                        Gerencie os bancos de dados MySQL vinculados a este servidor.
                    </p>
                </div>

                <div className="flex items-center gap-4">
                    <div className="text-sm text-gray-300 bg-(--color-secondary) px-4 py-2 rounded-lg">
                        Uso: <span className="text-white font-semibold">{databases.length}</span> / {maxDatabases === null ? 'Ilimitado' : maxDatabases}
                    </div>

                    <Button
                        variant="info"
                        onClick={handleCreateDatabase}
                        disabled={isCreating || isLimitReached}
                    >
                        {isCreating ? 'Criando...' : 'Novo'}
                    </Button>
                </div>
            </div>

            {/* Lista de Bancos de Dados */}
            {databases.length === 0 ? (
                <div className="flex flex-col items-center justify-center p-12 bg-(--color-secondary) rounded-md border-dashed shadown-(--card-shadow)">
                    <Database className="w-12 h-12 text-(--color-primary) mb-4" />
                    <h3 className="text-lg font-medium text-white mb-2">Nenhum banco de dados</h3>
                    <p className="text-gray-400 text-center max-w-sm">
                        Este servidor ainda não possui nenhum banco de dados MySQL criado. Clique no botão acima para provisionar um.
                    </p>
                </div>
            ) : (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {databases.map((db) => {
                        const showPassword = visiblePasswords[db.dbName] || false;
                        const isDeleting = deletingDb === db.dbName;
                        const endpoint = `${db.hostIp}:${db.hostPort}`;

                        return (
                            <Card key={db.dbName} title={`DATABASE: ${db.dbName}`}>
                                <div className="space-y-4">
                                    
                                    {/* Endpoint de Conexão */}
                                    <div>
                                        <label className="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wider">Host</label>
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

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {/* Usuário */}
                                        <div>
                                            <label className="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wider">Usuário</label>
                                            <div className="flex">
                                                <input 
                                                    readOnly 
                                                    value={db.dbUser} 
                                                    className="w-full bg-(--color-terciary) text-(--color-text-label) text-sm rounded-l-md px-3 py-2 focus:outline-none shadow-(--card-shadow)"
                                                />
                                                <button 
                                                    onClick={() => copyToClipboard(db.dbUser, "Usuário")}
                                                    className="bg-(--color-primary) hover:bg-(--color-primary)/50 text-(--color-text-label) px-3 rounded-r-md transition-colors"
                                                >
                                                    <Copy className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>

                                        {/* Senha */}
                                        <div>
                                            <label className="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wider">Senha</label>
                                            <div className="flex">
                                                <input 
                                                    readOnly 
                                                    type={showPassword ? "text" : "password"}
                                                    value={db.password} 
                                                    className="w-full bg-(--color-terciary) text-(--color-text-label) text-sm rounded-l-md px-3 py-2 focus:outline-none shadow-(--card-shadow)"
                                                />
                                                <button 
                                                    onClick={() => togglePasswordVisibility(db.dbName)}
                                                    className="bg-(--color-primary) hover:bg-(--color-primary)/50 text-(--color-text-label) px-3 transition-colors border-r border-(--color-secondary)"
                                                    title="Mostrar/Ocultar"
                                                >
                                                    {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                                                </button>
                                                <button 
                                                    onClick={() => copyToClipboard(db.password, "Senha")}
                                                    className="bg-(--color-primary) hover:bg-(--color-primary)/50 text-(--color-text-label) px-3 rounded-r-md transition-colors"
                                                    title="Copiar"
                                                >
                                                    <Copy className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {/* Linha Divisória e Botão Deletar */}
                                    <div className="border-t border-gray-800 pt-4 mt-4 flex justify-between items-center">
                                        <span className="text-xs text-(--color-text-label)">
                                            Criado em: {new Date(db.createdAt).toLocaleDateString('pt-BR')}
                                        </span>
                                        <Button
                                        variant="danger"
                                            onClick={() => handleDeleteDatabase(db.dbName)}
                                            disabled={isDeleting}
                                            className={`flex items-center gap-2 px-3 py-1.5`}
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