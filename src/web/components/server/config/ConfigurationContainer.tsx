import React, {useEffect, useRef, useState} from "react";
import { useServerContext } from "@/web/contexts/ServerContext";
import LoadingPage from "@/web/components/commons/LoadingPage";
import Card from "../../commons/components/Card";
import Input from "@/web/components/commons/components/Input";
import { useSession } from "@nytlex/auth/react";
import Button from "@/web/components/commons/components/Button";
import {useToast} from "@/web/contexts/ToastContext";

export default function ConfigurationContainer() {
    const { sendApiRequest, server, isLoadingServer, nodeIp, sftpPort, usage, sendServerAction, connectUsageWs, disconnectConsoleWs, refreshServer } = useServerContext();
    const nameRef = useRef<HTMLInputElement>(null);
    const descRef = useRef<HTMLInputElement>(null);
    const groupRef = useRef<HTMLInputElement>(null);
    const session = useSession();
    const toast = useToast()


    if (isLoadingServer) return <div className="min-h-screen flex justify-center items-center"><LoadingPage /></div>;
    if (!server) return null;


    async function handleReinstall() {
        if(usage?.state !== 'stopped') {
            toast.addToast("O servidor precisa estar desligado.", "error")
        } else {
            sendServerAction('install')
        }
    }

    async function handleSave() {
        try {
            const name = nameRef.current?.value;
            const desc = descRef.current?.value;
            const group = groupRef.current?.value
            const request = await sendApiRequest("/config", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name, desc, group
                })
            });

            const data = await request.json();

            if (!request.ok) {
                console.error(data.error);
                toast.addToast(data.error, "error");
                return;
            }

            toast.addToast("Configurações salvas com sucesso!", "success");
            refreshServer()
        } catch (error) {
            console.error("Erro ao salvar:", error);
            alert("Erro ao conectar com o servidor.");
        }
    }


    return (
        <main className="flex-1 flex flex-col py-6 px-4 md:py-8 md:px-10 xl:px-20 overflow-x-hidden animate-[fadeIn_0.4s_ease-out]">

            {/* Cabeçalho da Página idêntico ao layout do Admin */}
            <div className="flex justify-between items-end mb-8">
                <div>
                    <h1 className="text-3xl font-black tracking-tight text-[var(--color-text-value)] mb-2">Configurações</h1>
                    <p className="text-[var(--color-text-sub)] text-sm font-medium">
                        Gerencie detalhes, acesso SFTP e reinstalação do servidor.
                    </p>
                </div>
            </div>

            {/* Grid configurado para 2 colunas no desktop e 1 no mobile */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                <Card title="Detalhes do SFTP">
                    <div className="flex flex-col gap-4">
                        <Input
                            label={"ENDEREÇO DO SERVIDOR"}
                            value={`sftp://${nodeIp}:${sftpPort}`}
                            readOnly={true}
                            type={"text"}
                        />
                        <Input
                            label={"NOME DE USUÁRIO"}
                            value={`${session.data?.user.name}_${server.serverUuid.split('-')[0]}`}
                            readOnly={true}
                        />
                        <p className="text-[var(--color-text-label)]">Sua senha SFTP é a mesma que você usa para acessar este painel.</p>
                    </div>
                </Card>

                <Card title={"Alterar detalhes do servidor"}>
                    <div className="flex flex-col gap-4">
                        <Input
                            label={"NOME DO SERVIDOR"}
                            defaultValue={server.name}
                            ref={nameRef}
                            type={"text"}
                        />
                        <Input
                            label={"AGRUPAMENTO DO SERVIDOR"}
                            defaultValue={server.group}
                            ref={groupRef}
                            type={"text"}
                        />
                        <Input
                            as={"textarea"}
                            label={"DESCRIÇÃO DO SERVIDOR"}
                            defaultValue={server.description}
                            ref={descRef}
                            rows={4}
                        />
                        <Button onClick={handleSave} variant={"info"}>
                            SALVAR
                        </Button>
                    </div>
                </Card>


                <Card title={"REINSTALAR SERVIDOR"}>
                    <div className="flex flex-col gap-4">
                        <p className="text-[var(--color-text-label)]">
                            Para reinstalar o servidor é necessário desliga-lo, e irá executar novamente o script de instalação que o configurou inicialmente. Alguns arquivos podem ser excluídos ou modificados durante este processo. Faça um backup dos seus dados antes de continuar.
                        </p>
                        <Button disabled={usage?.state !== 'stopped'} onClick={handleReinstall} variant={"danger"}>
                            REINSTALAR
                        </Button>
                    </div>
                </Card>

            </div>
        </main>
    );
}