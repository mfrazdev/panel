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
        <main className="flex-1 flex flex-col py-6 px-4 md:py-8 md:px-10 xl:px-20 overflow-x-hidden animate-[fadeIn_0.4s_ease-out] gap-8">
            {/* Header no novo padrão Vatts */}
            <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 className="text-3xl font-black tracking-tight text-[var(--color-text-value)] mb-2 flex items-center gap-3">
                        <CalendarClock className="w-8 h-8 text-[var(--color-primary)]" />
                        Agendamentos
                    </h1>
                    <p className="text-[var(--color-text-sub)] text-sm font-medium">
                        Configure rotinas automáticas (Cron) para enviar comandos ou ações ao servidor.
                    </p>
                </div>

                <div className="flex items-center gap-4">
                    <Button variant="info" onClick={() => openModal()} className="flex items-center gap-2">
                        <Plus className="w-5 h-5" /> Novo Agendamento
                    </Button>
                </div>
            </div>

            <div>
                {schedulers.length === 0 ? (
                    /* Empty State Tracejado padrão Vatts */
                    <div className="flex flex-col items-center justify-center text-center py-20 rounded-2xl border-2 border-dashed border-white/5">
                        <div className="w-16 h-16 rounded-full bg-[var(--color-secondary)] border border-white/5 flex items-center justify-center text-[var(--color-text-sub)] mb-5 shadow-sm">
                            <Clock className="w-7 h-7" />
                        </div>
                        <div>
                            <p className="text-[16px] font-bold text-[var(--color-text-value)] tracking-tight">Nenhum agendamento</p>
                            <p className="text-[13px] font-medium text-[var(--color-text-sub)] mt-1.5 leading-relaxed max-w-sm mx-auto">
                                Você não possui nenhuma rotina configurada para este servidor.
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        {schedulers.map((sched) => (
                            <Card key={sched.id} title={sched.name}>
                                <div className="space-y-5">
                                    {/* Info Resumo */}
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-2 text-sm text-[var(--color-text-sub)] font-medium">
                                            <Clock className="w-4 h-4 text-[var(--color-primary)]" />
                                            <code className="bg-white/5 border border-white/10 px-2.5 py-1 rounded-md font-mono text-[var(--color-primary)] tracking-widest shadow-sm">
                                                {sched.cron}
                                            </code>
                                        </div>
                                        <div>
                                            <span className={`px-2 py-1 text-[10px] font-bold uppercase tracking-wider rounded-md ${sched.active ? 'bg-[var(--color-success)]/10 text-[var(--color-success)]' : 'bg-[var(--color-danger)]/10 text-[var(--color-danger)]'}`}>
                                                {sched.active ? "Ativo" : "Inativo"}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Tasks Preview */}
                                    <div className="bg-[var(--color-terciary)] border border-white/5 rounded-xl p-4 max-h-40 overflow-y-auto custom-scrollbar shadow-sm">
                                        <p className="text-[11px] font-bold text-[var(--color-text-sub)] mb-3 uppercase tracking-wider">
                                            Ações ({sched.tasks.length})
                                        </p>
                                        <div className="space-y-2.5">
                                            {sched.tasks.map((task, idx) => (
                                                <div key={idx} className="flex items-start gap-2.5 text-[13px] text-[var(--color-text-label)] font-medium">
                                                    {task.type === 'action' ? <Zap className="w-4 h-4 text-orange-400 mt-0.5 shrink-0" /> : <Terminal className="w-4 h-4 text-[var(--color-text-sub)] mt-0.5 shrink-0" />}
                                                    <span className="truncate">
                                                        <strong className="text-[var(--color-text-value)] capitalize">{task.type}:</strong> {task.payload}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Actions */}
                                    <div className="border-t border-white/5 pt-5 mt-5 flex justify-between items-center">
                                        <Button
                                            variant={sched.active ? "danger" : "success"}
                                            className="!py-2 !px-4 !text-[13px]"
                                            onClick={() => handleToggle(sched.id, sched.active)}
                                            disabled={togglingId === sched.id}
                                        >
                                            {togglingId === sched.id ? "Alterando..." : sched.active ? "Desativar" : "Ativar"}
                                        </Button>

                                        <div className="flex items-center gap-2">
                                            <Button
                                                variant="secondary"
                                                className="!py-2 !px-4 !text-[13px] flex items-center gap-1.5"
                                                onClick={() => openModal(sched)}
                                            >
                                                <Edit className="w-4 h-4" /> Editar
                                            </Button>

                                            {confirmDeleteId === sched.id ? (
                                                <div className="flex items-center gap-1">
                                                    <Button variant="danger" className="!py-2 !px-3 !text-[13px]" onClick={() => handleDelete(sched.id)}>
                                                        Certeza?
                                                    </Button>
                                                    <Button variant="secondary" className="!py-2 !px-3" onClick={() => setConfirmDeleteId(null)}>
                                                        <X className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            ) : (
                                                <Button
                                                    variant="danger"
                                                    className="!py-2 !px-3"
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

            {/* MODAL MELHORADO */}
            {isModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-[fadeIn_0.2s_ease-out]">
                    <div className="bg-[var(--color-secondary)] w-full max-w-2xl rounded-2xl shadow-2xl border border-white/5 overflow-hidden flex flex-col max-h-[90vh]">
                        {/* Header Modal */}
                        <div className="flex justify-between items-center p-6 border-b border-white/5 bg-white/[0.01]">
                            <h2 className="text-xl font-black text-[var(--color-text-value)] flex items-center gap-3 tracking-tight">
                                {editingId ? <Edit className="w-6 h-6 text-[var(--color-info)]" /> : <Plus className="w-6 h-6 text-[var(--color-success)]" />}
                                {editingId ? "Editar Agendamento" : "Novo Agendamento"}
                            </h2>
                            <button onClick={closeModal} className="text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] transition-colors p-1">
                                <X className="w-6 h-6" />
                            </button>
                        </div>

                        {/* Body Modal */}
                        <div className="p-6 overflow-y-auto flex-1 custom-scrollbar">
                            <form id="schedulerForm" onSubmit={handleSubmit} className="space-y-6">
                                {/* Informações Básicas */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div className="flex flex-col gap-1.5 w-full">
                                        <label className="font-bold text-[var(--color-text-sub)] text-[12px] uppercase tracking-wider ml-1">Nome</label>
                                        <input
                                            type="text"
                                            required
                                            value={formData.name}
                                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                            placeholder="Ex: Restart Diário"
                                            className="w-full bg-[var(--color-terciary)] text-[var(--color-text-value)] placeholder:text-[var(--color-text-sub)] p-4 rounded-xl border border-white/5 outline-none focus:ring-2 focus:ring-[var(--color-primary)] transition-all duration-200"
                                        />
                                    </div>
                                    <div className="flex flex-col gap-1.5 w-full">
                                        <label className="font-bold text-[var(--color-text-sub)] text-[12px] uppercase tracking-wider ml-1 flex items-center justify-between">
                                            Expressão Cron
                                            <span className="text-[10px] text-[var(--color-text-sub)] font-normal normal-case">(Min Hr Dia Mês DiaSemana)</span>
                                        </label>
                                        <input
                                            type="text"
                                            required
                                            value={formData.cron}
                                            onChange={(e) => setFormData({ ...formData, cron: e.target.value })}
                                            placeholder="*/5 * * * *"
                                            className="w-full bg-[var(--color-terciary)] text-[var(--color-text-value)] placeholder:text-[var(--color-text-sub)] p-4 rounded-xl border border-white/5 outline-none focus:ring-2 focus:ring-[var(--color-primary)] transition-all duration-200 font-mono tracking-widest"
                                        />
                                    </div>
                                </div>

                                {/* Status Ativo */}
                                <div className="flex items-center gap-3 px-1">
                                    <input
                                        type="checkbox"
                                        id="isActive"
                                        checked={formData.isActive}
                                        onChange={(e) => setFormData({ ...formData, isActive: e.target.checked })}
                                        className="w-4.5 h-4.5 rounded border border-white/10 bg-[var(--color-terciary)] text-[var(--color-primary)] focus:ring-[var(--color-primary)] focus:ring-offset-0 cursor-pointer"
                                    />
                                    <label htmlFor="isActive" className="text-[14px] font-medium text-[var(--color-text-label)] cursor-pointer select-none">
                                        Ativar agendamento imediatamente após salvar
                                    </label>
                                </div>

                                {/* Lista de Tarefas */}
                                <div className="border-t border-white/5 pt-6 mt-6">
                                    <div className="flex items-center justify-between mb-4">
                                        <h3 className="font-bold text-[var(--color-text-sub)] text-[12px] uppercase tracking-wider ml-1">Ações da Tarefa</h3>
                                        <button
                                            type="button"
                                            onClick={handleAddTask}
                                            className="text-[12px] font-bold uppercase tracking-wider flex items-center gap-1.5 bg-[var(--color-info)]/10 text-[var(--color-info)] hover:bg-[var(--color-info)]/20 px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                                        >
                                            <Plus className="w-3.5 h-3.5" /> Adicionar Ação
                                        </button>
                                    </div>

                                    <div className="space-y-3">
                                        {formData.tasks.map((task, index) => (
                                            <div key={index} className="flex flex-col md:flex-row gap-3 items-start md:items-center bg-white/[0.02] p-3 rounded-xl border border-white/5">
                                                <span className="bg-[var(--color-terciary)] text-[var(--color-text-sub)] font-bold text-[12px] px-2.5 py-1.5 rounded-lg w-8 text-center shrink-0 border border-white/5">
                                                    {index + 1}
                                                </span>
                                                <select
                                                    value={task.type}
                                                    onChange={(e) => handleTaskChange(index, "type", e.target.value as "action" | "command")}
                                                    className="w-full md:w-48 bg-[var(--color-terciary)] border border-white/5 text-[var(--color-text-value)] rounded-lg px-3 py-2.5 text-[14px] font-medium focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] cursor-pointer"
                                                >
                                                    <option value="command">Console Command</option>
                                                    <option value="action">Power Action</option>
                                                </select>

                                                {task.type === "action" ? (
                                                    <select
                                                        value={task.payload}
                                                        onChange={(e) => handleTaskChange(index, "payload", e.target.value)}
                                                        className="w-full flex-1 bg-[var(--color-terciary)] border border-white/5 text-[var(--color-text-value)] rounded-lg px-3 py-2.5 text-[14px] font-medium focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] cursor-pointer"
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
                                                        className="w-full flex-1 bg-[var(--color-terciary)] border border-white/5 text-[var(--color-text-value)] placeholder:text-[var(--color-text-sub)] rounded-lg px-3 py-2.5 text-[14px] font-medium focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]"
                                                    />
                                                )}

                                                <button
                                                    type="button"
                                                    onClick={() => handleRemoveTask(index)}
                                                    disabled={formData.tasks.length === 1}
                                                    className="p-2.5 text-[var(--color-danger)] hover:bg-[var(--color-danger)]/10 rounded-lg disabled:opacity-30 shrink-0 transition-colors cursor-pointer"
                                                    title="Remover Ação"
                                                >
                                                    <X className="w-4.5 h-4.5" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                    <p className="text-[12px] text-[var(--color-text-sub)] font-medium mt-3 flex items-center gap-1.5 ml-1">
                                        <AlertCircle className="w-3.5 h-3.5 text-[var(--color-warning)]" />
                                        As ações serão executadas na ordem exibida acima.
                                    </p>
                                </div>
                            </form>
                        </div>

                        {/* Footer Modal */}
                        <div className="p-6 border-t border-white/5 bg-white/[0.01] flex justify-end gap-3">
                            <Button variant="ghost" onClick={closeModal} disabled={isSubmitting}>
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