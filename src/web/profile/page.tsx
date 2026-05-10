import React, { useRef } from "react";
import DashboardWrapper from "@/web/components/wrappers/Wrapper";
import Card from "@/web/components/commons/components/Card";
import Input from "@/web/components/commons/components/Input";
import Button from "@/web/components/commons/components/Button";
import { AuthGuard } from "@vatts/auth/react";
import {Link} from "vatts/react"; // Ajuste o import do Link conforme o seu roteador (Next.js ou React Router)

export default function ProfilePage() {
    const newEmailRef = useRef<HTMLInputElement>(null);
    const passwordRef = useRef<HTMLInputElement>(null);

    async function handleEmailChange() {
        // Lógica para trocar o email aqui
        const newEmail = newEmailRef.current?.value;
        const password = passwordRef.current?.value;

        console.log("Trocando email para:", newEmail, "com a senha:", password);
    }

    return (
        <AuthGuard redirectTo={'/'}>
            <main className="flex-1 flex flex-col py-6 px-4 md:py-8 md:px-10 xl:px-20 overflow-x-hidden">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                    {/* Card para alterar o E-mail */}
                    <Card title={"Alterar Endereço de E-mail"}>
                        <div className="flex flex-col gap-4">
                            <p className={"text-(--color-text-label)"}>
                                Para alterar seu e-mail, insira o novo endereço e confirme utilizando sua senha atual.
                            </p>
                            <Input
                                label={"NOVO E-MAIL"}
                                ref={newEmailRef}
                                type={"email"}
                            />
                            <Input
                                label={"SENHA ATUAL"}
                                ref={passwordRef}
                                type={"password"}
                            />
                            <Button onClick={handleEmailChange} variant={"info"}>
                                SALVAR ALTERAÇÕES
                            </Button>
                        </div>
                    </Card>

                    {/* Card para Recuperação/Troca de Senha */}
                    <Card title={"Segurança e Senha"}>
                        <div className="flex flex-col gap-4">
                            <p className={"text-(--color-text-label)"}>
                                Caso deseje alterar ou recuperar a sua senha, clique no botão abaixo para ser redirecionado à página de recuperação.
                            </p>
                            <Link href="/auth/recovery">
                                <Button variant={"danger"} className="w-full">
                                    RECUPERAR SENHA
                                </Button>
                            </Link>
                        </div>
                    </Card>

                </div>
            </main>
        </AuthGuard>
    );
}