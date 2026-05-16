import React, { useRef } from "react";
import DashboardWrapper from "@/web/components/wrappers/Wrapper";
import Card from "@/web/components/commons/components/Card";
import Input from "@/web/components/commons/components/Input";
import Button from "@/web/components/commons/components/Button";
import {AuthGuard, useSession} from "@vatts/auth/react";
import {Link} from "vatts/react";
import {useToast} from "@/web/contexts/ToastContext"; // Ajuste o import do Link conforme o seu roteador (Next.js ou React Router)

export default function ProfilePage() {
    const toast = useToast()
    const newEmailRef = useRef<HTMLInputElement>(null);
    const passwordRef = useRef<HTMLInputElement>(null);
    const session = useSession()
    async function handleEmailChange() {
        try {
            const newEmail = newEmailRef.current?.value;
            const password = passwordRef.current?.value;

            const response = await fetch(`/api/v1/users/email`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ currentPassword: password, newEmail })
            });

            // Se for 200-299
            if (response.ok) {
                session.update(); // Atualiza a sessão para refletir o novo e-mail
                toast.addToast('E-mail alterado com sucesso!', 'success');
                return;
            }

            // Se chegou aqui, é um erro (400, 500, etc)
            // Vamos tentar ler o JSON, mas sem quebrar se não for JSON
            let errorMessage = 'Erro ao alterar o e-mail. Por favor, tente novamente.';
            const text = await response.text(); // Tenta ler como texto primeiro
            try {
                const data = JSON.parse(text); // Tenta parsear como JSON
                errorMessage = data.error || errorMessage;
            } catch (parseError) {
                // Se cair aqui, o corpo não era JSON (pode ser texto puro ou vazio)
                console.warn("Resposta do servidor não é JSON", text);
            }

            toast.addToast(errorMessage, 'error');

        } catch (error) {
            // Só entra aqui se a requisição falhar (erro de rede)
            console.error("Erro de rede ao alterar o e-mail:", error);
            toast.addToast('Erro de conexão. Verifique sua internet.', 'error');
        }
    }

    return (
        <AuthGuard redirectTo={'/'}>
            <main className="flex-1 flex flex-col py-6 px-4 md:py-8 md:px-10 xl:px-20 overflow-x-hidden">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                    {/* Card para alterar o E-mail */}
                    <Card title={"Alterar Endereço de E-mail"}>
                        <div className="flex flex-col gap-4">
                            <p className={"text-(--color-text-label)"}>
                                Email é um dado sensível e importante para a segurança da sua conta.
                                Seu e-mail atual é <strong>{session.data?.user.email}</strong>.
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