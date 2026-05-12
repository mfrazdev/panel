import React, { useEffect, useState } from "react";
import { useServerContext } from "@/web/contexts/ServerContext";
import LoadingPage from "@/web/components/commons/LoadingPage";
import Card from "@/web/components/commons/components/Card";
import Button from "@/web/components/commons/components/Button";
import { useToast } from "@/web/contexts/ToastContext";
import {
    CalendarClock,
    Plus,
    Trash2,
    Edit,
    Clock,
    Terminal,
    Zap,
    X,
    Check,
    AlertCircle
} from "lucide-react";

type Task = {
    type: "action" | "command";
    payload: string;
};

type Scheduler = {
    id: string;
    name: string;
    cron: string;
    active: boolean;
    tasks: Task[];
};

type SchedulerFormData = {
    name: string;
    cron: string;
    isActive: boolean;
    tasks: Task[];
};

const DEFAULT_FORM_DATA: SchedulerFormData = {
    name: "",
    cron: "0 12 * * *",
    isActive: true,
    tasks: [{ type: "command", payload: "" }]
};

export default function SchedulersContainer() {
    const { sendApiRequest, server, isLoadingServer } = useServerContext();
    const [isLoading, setIsLoading] = useState(true);
    const [schedulers, setSchedulers] = useState<Scheduler[]>([]);
    const toast = useToast();

    // Modal & Form States
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [editingId, setEditingId] = useState<string | null>(null);
    const [formData, setFormData] = useState<SchedulerFormData>(DEFAULT_FORM_DATA);

    // Confirmation states
    const [confirmDeleteId, setConfirmDeleteId] = useState<string | null>(null);
    const [togglingId, setTogglingId] = useState<string | null>(null);

    const loadSchedulers = async () => {
        if (!server) return;
        setIsLoading(true);
        try {
            const request = await sendApiRequest("/schedulers");
            const data = await request.json();

            if (!request.ok) {
                toast.addToast(data.error || "Erro ao carregar agendamentos.", "error");
                return;
            }

            setSchedulers(Array.isArray(data.schedulers) ? data.schedulers : []);
        } catch (error) {
            console.error("Erro ao carregar agendamentos:", error);
            toast.addToast("Erro ao conectar com o servidor.", "error");
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        if (!server) return;
        loadSchedulers();
    }, [server]);

    const handleAddTask = () => {
        setFormData(prev => ({
            ...prev,
            tasks: [...prev.tasks, { type: "command", payload: "" }]
        }));
    };

    const handleRemoveTask = (index: number) => {
        if (formData.tasks.length <= 1) return;
        const newTasks = [...formData.tasks];
        newTasks.splice(index, 1);
        setFormData({ ...formData, tasks: newTasks });
    };

    const handleTaskChange = (index: number, field: keyof Task, value: string) => {
        const newTasks = [...formData.tasks];
        newTasks[index] = { ...newTasks[index], [field]: value };
        setFormData({ ...formData, tasks: newTasks });
    };

    const openModal = (scheduler?: Scheduler) => {
        if (scheduler) {
            setEditingId(scheduler.id);
            setFormData({
                name: scheduler.name,
                cron: scheduler.cron,
                isActive: scheduler.active,
                tasks: [...scheduler.tasks]
            });
        } else {
            setEditingId(null);
            setFormData(DEFAULT_FORM_DATA);
        }
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setEditingId(null);
        setFormData(DEFAULT_FORM_DATA);
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!formData.name || !formData.cron) {
            toast.addToast("Preencha o nome e o cron.", "error");
            return;
        }

        const emptyTasks = formData.tasks.some(t => !t.payload.trim());
        if (emptyTasks) {
            toast.addToast("Preencha o payload de todas as tasks.", "error");
            return;
        }

        setIsSubmitting(true);
        const endpoint = editingId ? "/schedulers/edit" : "/schedulers/create";

        try {
            const bodyData = editingId
                ? { schedulerId: editingId, ...formData }
                : formData;

            const request = await sendApiRequest(endpoint, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(bodyData)
            });
            const data = await request.json();

            if (request.ok) {
                toast.addToast(editingId ? "Agendamento salvo." : "Agendamento criado.", "success");
                loadSchedulers();
                closeModal();
            } else {
                toast.addToast(data.error || "Erro ao salvar agendamento.", "error");
            }
        } catch (error) {
            console.error("Erro ao salvar:", error);
            toast.addToast("Erro de conexão.", "error");
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleDelete = async (schedulerId: string) => {
        try {
            const request = await sendApiRequest("/schedulers/delete", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ schedulerId })
            });
            const data = await request.json();

            if (request.ok) {
                toast.addToast("Agendamento removido.", "success");
                setSchedulers(prev => prev.filter(s => s.id !== schedulerId));
            } else {
                toast.addToast(data.error || "Erro ao remover.", "error");
            }
        } catch (error) {
            toast.addToast("Erro de conexão.", "error");
        } finally {
            setConfirmDeleteId(null);
        }
    };

    const handleToggle = async (schedulerId: string, currentStatus: boolean) => {
        setTogglingId(schedulerId);
        try {
            const newStatus = !currentStatus;
            const request = await sendApiRequest("/schedulers/toggle", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ schedulerId, isActive: newStatus })
            });
            const data = await request.json();

            if (request.ok) {
                toast.addToast(`Agendamento ${newStatus ? "ativado" : "desativado"}.`, "success");
                setSchedulers(prev => prev.map(s => s.id === schedulerId ? { ...s, active: newStatus } : s));
            } else {
                toast.addToast(data.error || "Erro ao alterar status.", "error");
            }
        } catch (error) {
            toast.addToast("Erro de conexão.", "error");
        } finally {
            setTogglingId(null);
        }
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
        <main className="flex-1 flex flex-col py-6 px-4 md:py-8 md:px-10 xl:px-20 overflow-x-hidden gap-8">
            {/* Header */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-(--color-text-label) flex items-center gap-2">
                        <CalendarClock className="w-6 h-6 text-(--info)" />
                        Agendamentos
                    </h1>
                    <p className="text-gray-400 mt-1">
                        Configure rotinas automáticas (Cron) para enviar comandos ou ações ao servidor.
                    </p>
                </div>

                <Button variant="info" onClick={() => openModal()} className="flex items-center gap-2">
                    <Plus className="w-4 h-4" /> Novo Agendamento
                </Button>
            </div>

            { }
            <div>
                {schedulers.length === 0 ? (
                    <div className="flex flex-col items-center justify-center p-12 bg-(--color-secondary) rounded-md border-dashed shadow-(--card-shadow)">
                        <Clock className="w-12 h-12 text-gray-500 mb-4" />
                        <h3 className="text-lg font-medium text-white mb-2">Nenhum agendamento</h3>
                        <p className="text-gray-400 text-center max-w-sm">
                            Você não possui nenhuma rotina configurada para este servidor.
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        {schedulers.map((sched) => (
                            <Card key={sched.id} title={sched.name}>
                                <div className="space-y-4">
                                    {/* Info Resumo */}
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-2 text-sm text-gray-300">
                                            <Clock className="w-4 h-4 text-blue-400" />
                                            <code className="bg-black/30 px-2 py-1 rounded font-mono text-blue-300">
                                                {sched.cron}
                                            </code>
                                        </div>
                                        <div>
                                            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${sched.active ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'}`}>
                                                {sched.active ? "Ativo" : "Inativo"}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Tasks Preview */}
                                    <div className="bg-(--color-terciary) rounded-md p-3 max-h-32 overflow-y-auto">
                                        <p className="text-xs font-semibold text-gray-400 mb-2 uppercase tracking-wider">
                                            Ações ({sched.tasks.length})
                                        </p>
                                        <div className="space-y-2">
                                            {sched.tasks.map((task, idx) => (
                                                <div key={idx} className="flex items-start gap-2 text-sm text-gray-300">
                                                    {task.type === 'action' ? <Zap className="w-4 h-4 text-orange-400 mt-0.5 shrink-0" /> : <Terminal className="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />}
                                                    <span className="truncate">
                                                        <strong className="text-white capitalize">{task.type}:</strong> {task.payload}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Actions */}
                                    <div className="border-t border-gray-800 pt-4 mt-4 flex justify-between items-center">
                                        <Button
                                            variant={sched.active ? "danger" : "success"}
                                            className="px-3 py-1.5 text-sm"
                                            onClick={() => handleToggle(sched.id, sched.active)}
                                            disabled={togglingId === sched.id}
                                        >
                                            {togglingId === sched.id ? "Alterando..." : sched.active ? "Desativar" : "Ativar"}
                                        </Button>

                                        <div className="flex items-center gap-2">
                                            <Button
                                                variant="secondary"
                                                className="px-3 py-1.5 flex items-center gap-1"
                                                onClick={() => openModal(sched)}
                                            >
                                                <Edit className="w-4 h-4" /> Editar
                                            </Button>

                                            {confirmDeleteId === sched.id ? (
                                                <div className="flex items-center gap-1">
                                                    <Button variant="danger" className="px-3 py-1.5 text-xs font-bold" onClick={() => handleDelete(sched.id)}>
                                                        Certeza?
                                                    </Button>
                                                    <Button variant="secondary" className="px-3 py-1.5" onClick={() => setConfirmDeleteId(null)}>
                                                        <X className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            ) : (
                                                <Button
                                                    variant="danger"
                                                    className="px-3 py-1.5 text-gray-300 hover:text-white"
                                                    onClick={() => setConfirmDeleteId(sched.id)}
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            {}
            {isModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                    <div className="bg-(--color-secondary) w-full max-w-2xl rounded-xl shadow-2xl border border-gray-800 overflow-hidden flex flex-col max-h-[90vh]">
                        <div className="flex justify-between items-center p-4 border-b border-gray-800 bg-(--color-terciary)/50">
                            <h2 className="text-lg font-bold text-white flex items-center gap-2">
                                {editingId ? <Edit className="w-5 h-5 text-blue-400" /> : <Plus className="w-5 h-5 text-green-400" />}
                                {editingId ? "Editar Agendamento" : "Novo Agendamento"}
                            </h2>
                            <button onClick={closeModal} className="text-gray-400 hover:text-white p-1">
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <div className="p-4 overflow-y-auto flex-1 custom-scrollbar">
                            <form id="schedulerForm" onSubmit={handleSubmit} className="space-y-5">
                                {/* Informações Básicas */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-300 mb-1">Nome</label>
                                        <input
                                            type="text"
                                            required
                                            value={formData.name}
                                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                            placeholder="Ex: Restart Diário"
                                            className="w-full bg-(--color-terciary) border border-gray-700 text-white rounded-md px-3 py-2 focus:outline-none focus:border-blue-500 transition-colors"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-300 mb-1">
                                            Expressão Cron <span className="text-xs text-gray-500 font-normal">(Min Hora Dia Mês DiaSemana)</span>
                                        </label>
                                        <input
                                            type="text"
                                            required
                                            value={formData.cron}
                                            onChange={(e) => setFormData({ ...formData, cron: e.target.value })}
                                            placeholder="*/5 * * * *"
                                            className="w-full bg-(--color-terciary) border border-gray-700 text-white rounded-md px-3 py-2 focus:outline-none focus:border-blue-500 font-mono transition-colors"
                                        />
                                    </div>
                                </div>

                                {/* Status Ativo */}
                                <div className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="isActive"
                                        checked={formData.isActive}
                                        onChange={(e) => setFormData({ ...formData, isActive: e.target.checked })}
                                        className="w-4 h-4 rounded bg-gray-900 border-gray-700 text-blue-500 focus:ring-blue-600 focus:ring-offset-gray-900"
                                    />
                                    <label htmlFor="isActive" className="text-sm font-medium text-gray-300 cursor-pointer">
                                        Ativar agendamento imediatamente
                                    </label>
                                </div>

                                {/* Lista de Tarefas */}
                                <div className="border-t border-gray-800 pt-4">
                                    <div className="flex items-center justify-between mb-3">
                                        <h3 className="text-sm font-semibold text-gray-300 uppercase tracking-wider">Ações da Tarefa</h3>
                                        <button
                                            type="button"
                                            onClick={handleAddTask}
                                            className="text-xs flex items-center gap-1 bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 px-2 py-1 rounded transition-colors"
                                        >
                                            <Plus className="w-3 h-3" /> Adicionar Ação
                                        </button>
                                    </div>

                                    <div className="space-y-3">
                                        {formData.tasks.map((task, index) => (
                                            <div key={index} className="flex flex-col md:flex-row gap-2 items-start md:items-center bg-black/20 p-2 rounded-md border border-gray-800/50">
                                                <span className="bg-gray-800 text-gray-400 text-xs px-2 py-1 rounded w-6 text-center shrink-0">
                                                    {index + 1}
                                                </span>
                                                <select
                                                    value={task.type}
                                                    onChange={(e) => handleTaskChange(index, "type", e.target.value as "action" | "command")}
                                                    className="w-full md:w-40 bg-(--color-terciary) border border-gray-700 text-white rounded px-2 py-1.5 text-sm focus:outline-none focus:border-blue-500"
                                                >
                                                    <option value="command">Console Command</option>
                                                    <option value="action">Power Action</option>
                                                </select>

                                                {task.type === "action" ? (
                                                    <select
                                                        value={task.payload}
                                                        onChange={(e) => handleTaskChange(index, "payload", e.target.value)}
                                                        className="w-full flex-1 bg-(--color-terciary) border border-gray-700 text-white rounded px-2 py-1.5 text-sm focus:outline-none focus:border-blue-500"
                                                    >
                                                        <option value="" disabled>Selecione a ação...</option>
                                                        <option value="start">Iniciar (Start)</option>
                                                        <option value="restart">Reiniciar (Restart)</option>
                                                        <option value="stop">Desligar (Stop)</option>
                                                        <option value="kill">Forçar Parada (Kill)</option>
                                                    </select>
                                                ) : (
                                                    <input
                                                        type="text"
                                                        required
                                                        value={task.payload}
                                                        onChange={(e) => handleTaskChange(index, "payload", e.target.value)}
                                                        placeholder="Ex: say Reiniciando!"
                                                        className="w-full flex-1 bg-(--color-terciary) border border-gray-700 text-white rounded px-2 py-1.5 text-sm focus:outline-none focus:border-blue-500"
                                                    />
                                                )}

                                                <button
                                                    type="button"
                                                    onClick={() => handleRemoveTask(index)}
                                                    disabled={formData.tasks.length === 1}
                                                    className="p-1.5 text-red-400 hover:bg-red-400/10 rounded disabled:opacity-30 shrink-0"
                                                    title="Remover Ação"
                                                >
                                                    <X className="w-4 h-4" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                    <p className="text-xs text-gray-500 mt-2 flex items-center gap-1">
                                        <AlertCircle className="w-3 h-3" /> As ações serão executadas na ordem acima.
                                    </p>
                                </div>
                            </form>
                        </div>

                        <div className="p-4 border-t border-gray-800 bg-(--color-terciary)/30 flex justify-end gap-3">
                            <Button variant="secondary" onClick={closeModal} disabled={isSubmitting}>
                                Cancelar
                            </Button>
                            <Button
                                variant="success"
                                form="schedulerForm"
                                type="submit"
                                disabled={isSubmitting}
                                className="flex items-center gap-2"
                            >
                                <Check className="w-4 h-4" />
                                {isSubmitting ? "Salvando..." : editingId ? "Salvar Edição" : "Criar Agendamento"}
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </main>
    );
}