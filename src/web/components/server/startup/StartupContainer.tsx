import React, { useEffect, useRef, useState } from "react";
import { useServerContext } from "@/web/contexts/ServerContext";
import LoadingPage from "@/web/components/commons/LoadingPage";
import Card from "../../commons/components/Card";
import Input from "@/web/components/commons/components/Input";
import Select from "@/web/components/commons/components/Select";
import Checkbox from "@/web/components/commons/components/Checkbox";
import Button from "@/web/components/commons/components/Button";
import { useToast } from "@/web/contexts/ToastContext";

export default function StartupContainer() {
    const { sendApiRequest, server, isLoadingServer, usage, sendServerAction } = useServerContext();
    const [startupData, setStartupData] = useState<any>(null);
    const [isLoadingStartup, setIsLoadingStartup] = useState(true);
    const [envVars, setEnvVars] = useState<Record<string, string>>({});

    const [startupCommandReplaced, setCommand] = useState("");
    const [selectedDockerImage, setSelectedDockerImage] = useState<string>("");

    const toast = useToast();
    const debounceTimer = useRef<NodeJS.Timeout | null>(null);

    const isServerStopped = usage?.state === 'stopped';

    useEffect(() => {
        if (!server) return;

        async function fetchStartup() {
            try {
                const request = await sendApiRequest("/startup");
                const data = await request.json();

                if (request.ok) {
                    setStartupData(data);
                    // @ts-ignore
                    setEnvVars(JSON.parse(server.envVars || "{}") || {});

                    const parsedDockerImages = JSON.parse(data.core.dockerImages || "[]");
                    // @ts-ignore
                    setSelectedDockerImage(server.dockerImage || parsedDockerImages[0]?.image || "");
                } else {
                    toast.addToast(data.error || "Erro ao carregar dados de inicialização.", "error");
                }
            } catch (error) {
                console.error("Erro ao buscar startup:", error);
                toast.addToast("Erro ao conectar com o servidor.", "error");
            } finally {
                setIsLoadingStartup(false);
            }
        }

        fetchStartup();
    }, [server]);

    const handleDockerImageChange = async (value: string) => {
        setSelectedDockerImage(value);

        try {
            const request = await sendApiRequest("/startup/docker", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ dockerImage: value })
            });
            const text = await request.text();
            const data = JSON.parse(text);

            if (request.ok) {
                toast.addToast(data.message || "Imagem Docker atualizada com sucesso!", "success");
            } else {
                toast.addToast(data.error || "Erro ao atualizar imagem Docker.", "error");
            }
        } catch (error) {
            console.error("Erro ao salvar imagem docker:", error);
            toast.addToast("Erro de conexão ao salvar a imagem.", "error");
        }
    };

    const handleVarChange = (envKey: string, value: string) => {
        setEnvVars(prev => ({ ...prev, [envKey]: value }));

        if (debounceTimer.current) {
            clearTimeout(debounceTimer.current);
        }

        debounceTimer.current = setTimeout(async () => {
            try {
                const request = await sendApiRequest("/startup/variable", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ key: envKey, value: value })
                });
                const data = await request.json();

                if (request.ok) {
                    toast.addToast(data.message || "Variável salva com sucesso!", "success");
                } else {
                    toast.addToast(data.error || "Erro ao salvar variável.", "error");
                }
            } catch (error) {
                console.error("Erro ao salvar variável:", error);
                toast.addToast("Erro de conexão ao salvar variável.", "error");
            }
        }, 500);
    };

    async function handleReinstall() {
        if (!isServerStopped) {
            toast.addToast("O servidor precisa estar desligado para reinstalar.", "error");
            return;
        }
        sendServerAction('install');
        toast.addToast("Processo de reinstalação iniciado.", "success");
    }

    useEffect(() => {
        if (server) {
            let command = server.startupCommand;
            if (server.envVars) {
                try {
                    for (const [key, value] of Object.entries(JSON.parse(server.envVars))) {
                        command = command?.replace(`{{${key}}}`, `${value}`);
                    }
                } catch (e) {
                    console.error(e);
                }
            }
            command = command?.replace("{{SERVER_PORT}}", `${server.allocation?.port ?? 0}`);
            setCommand(command ?? '');
        }
    }, [server?.envVars, server?.allocation]);

    if (isLoadingServer || isLoadingStartup) return <div className="min-h-screen flex justify-center items-center"><LoadingPage /></div>;
    if (!server || !startupData) return null;

    const core = startupData.core;
    const variables = JSON.parse(core.variables || "[]");
    const dockerImages = JSON.parse(core.dockerImages || "[]");

    const dockerOptions = dockerImages.map((img: any) => ({
        label: img.name,
        value: img.image
    }));

    return (
        <main className="flex-1 flex flex-col py-6 px-4 md:py-8 md:px-10 xl:px-20 overflow-x-hidden gap-8">

            {/* Header da Página */}
            <div>
                <h1 className="text-3xl font-black tracking-tight text-[var(--color-text-value)] mb-2">Inicialização</h1>
                <p className="text-[var(--color-text-sub)] text-[14px] font-medium">
                    Gerencie parâmetros de inicialização, imagem Docker e reinstalação.
                </p>
            </div>

            {/* Linha 1: Comando (2/3 da tela) + Docker (1/3 da tela) */}
            <div className="grid grid-cols-1 lg:grid-cols-[2fr_1fr] gap-6 items-start">
                <Card title="COMANDO DE INICIALIZAÇÃO">
                    <Input
                        value={startupCommandReplaced}
                        readOnly={true}
                        desc="O comando base utilizado para iniciar o servidor."
                    />
                </Card>

                <Card title="IMAGEM DOCKER">
                    <Select
                        options={dockerOptions}
                        value={selectedDockerImage}
                        onChange={handleDockerImageChange}
                        desc="Recurso avançado para selecionar o ambiente virtual."
                    />
                </Card>
            </div>

            {/* Linha 2: Reinstalar (Card Horizontal Full Width pra respirar) */}
            <Card title="ZONA DE PERIGO: REINSTALAR SERVIDOR">
                <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div className="flex flex-col gap-3 flex-1">
                        <div className={`w-fit px-3 py-1.5 rounded-xl flex items-center gap-2 transition-colors duration-300 ${isServerStopped ? 'bg-[var(--color-success)]/10 text-[var(--color-success)]' : 'bg-[var(--color-danger)]/10 text-[var(--color-danger)]'}`}>
                            <span className="text-[10px] font-black uppercase tracking-widest opacity-80">Status:</span>
                            <span className="text-[13px] font-bold">
                                {isServerStopped ? 'Servidor desligado (Pronto para reinstalar)' : 'Ação bloqueada, servidor precisa estar desligado'}
                            </span>
                        </div>
                        <p className="text-[13px] leading-relaxed text-[var(--color-text-sub)] max-w-3xl">
                            Reexecuta o script de instalação original. <span className="text-[var(--color-warning)] font-bold">Faça backup</span> antes de continuar, pois arquivos podem ser apagados ou substituídos de forma irreversível durante este processo.
                        </p>
                    </div>

                    <Button
                        variant="danger"
                        disabled={!isServerStopped}
                        onClick={handleReinstall}
                        className="w-full md:w-auto shrink-0 px-8 py-3 font-black tracking-wider shadow-lg"
                    >
                        REINSTALAR
                    </Button>
                </div>
            </Card>

            {/* Variáveis */}
            <div className="mt-2">
                <h2 className="text-xl font-bold text-[var(--color-text-value)] mb-5 tracking-tight">Variáveis de Ambiente</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start">
                    {variables.map((v: any) => {
                        const rulesArray = v.rules.split('|');
                        const isBoolean = rulesArray.includes('in:true,false') || rulesArray.includes('in:false,true');
                        const inRule = rulesArray.find((r: string) => r.startsWith('in:') && !isBoolean);
                        const isNumeric = rulesArray.includes('numeric');

                        const currentValue = envVars[v.envVariable] || '';

                        return (
                            <Card key={v.envVariable} title={v.name.toUpperCase()}>
                                {isBoolean ? (
                                    <Checkbox
                                        checked={currentValue === 'true'}
                                        onChange={(checked) => handleVarChange(v.envVariable, checked ? 'true' : 'false')}
                                        desc={v.description}
                                        label=""
                                    />
                                ) : inRule ? (
                                    <Select
                                        options={inRule.replace('in:', '').split(',').map((opt: string) => ({
                                            label: opt,
                                            value: opt
                                        }))}
                                        value={currentValue}
                                        onChange={(val) => handleVarChange(v.envVariable, val as string)}
                                        desc={v.description}
                                    />
                                ) : (
                                    <Input
                                        type={isNumeric ? "number" : "text"}
                                        value={currentValue}
                                        onChange={(e: any) => handleVarChange(v.envVariable, e.target.value)}
                                        desc={v.description}
                                    />
                                )}
                            </Card>
                        );
                    })}
                </div>
            </div>
        </main>
    );
}