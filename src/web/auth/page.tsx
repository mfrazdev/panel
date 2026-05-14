import React, { useState } from 'react';
import { GuestOnly, useSession } from "@vatts/auth/react";
import { useToast } from "@/web/contexts/ToastContext";
import { Link, router, VattsImage } from "vatts/react";
import Input from "@/web/components/commons/components/Input";
import Button from "@/web/components/commons/components/Button";
import Footer from "@/web/components/commons/Footer";
import Card from "@/web/components/commons/components/Card";

export default function App() {
    const session = useSession();
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const toast = useToast();

    const isDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    const urlImage = isDark ? '/assets/img/logo-white.png' : '/assets/img/logo-dark.png';

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        const sign = await session.signIn('credentials', {
            email: username,
            password: password,
            redirect: false
        });
        if (sign && sign.ok === true) {
            toast.addToast('Login realizado com sucesso!', 'success');
            router.push('/');
        } else {
            toast.addToast('Erro ao realizar login. Verifique suas credenciais.', 'error');
        }
    };

    return (
        <GuestOnly redirectTo="/">
            <div className="min-h-screen flex flex-col relative bg-[var(--color-background)]">
                {/* flex-1 garante que o conteúdo central empurre o footer lá para baixo */}
                <div className="flex-1 flex flex-col justify-center items-center px-4 py-12 animate-[fadeIn_0.4s_ease-out]">

                    <div className="w-full max-w-4xl">
                        {/* Título fora do card para dar respiro */}
                        <div className="text-center mb-10">
                            <h1 className="text-3xl md:text-4xl font-black tracking-tight text-[var(--color-text-value)]">
                                Autenticação
                            </h1>
                            <p className="text-[var(--color-text-sub)] mt-2 font-medium">
                                Faça login para acessar o painel de controle
                            </p>
                        </div>

                        <Card>
                            {/* Ajustado para ser 1 coluna no mobile, e 2 no Desktop */}
                            <div className="grid grid-cols-1 md:grid-cols-[1fr_1.5fr] gap-6 md:gap-10 items-center">

                                {/* Lado Esquerdo - Logo (Escondido no mobile, exibido em telas maiores com fundo destacado) */}
                                <div className="hidden md:flex justify-center items-center p-8  rounded-xl h-full shadow-inner">
                                    <VattsImage
                                        src={urlImage}
                                        width={240}
                                        className="hover:scale-105 transition-transform duration-500 drop-shadow-xl"
                                    />
                                </div>

                                {/* Lado Direito - Formulário */}
                                <div className="w-full flex flex-col p-2 md:py-6 md:pr-6">
                                    {/* Logo aparece apenas no Mobile */}
                                    <div className="md:hidden flex justify-center mb-8">
                                        <VattsImage src={urlImage} width={180} />
                                    </div>

                                    <form onSubmit={handleSubmit} className="space-y-6 w-full">
                                        {/* O nosso Input atualizado já possui a prop 'label', então removemos as labels soltas! */}
                                        <Input
                                            label="Nome de Usuário ou Email"
                                            type="text"
                                            value={username}
                                            onChange={(e) => setUsername(e.target.value)}
                                            placeholder="admin@vatts.js"
                                        />

                                        <Input
                                            label="Senha"
                                            type="password"
                                            value={password}
                                            onChange={(e) => setPassword(e.target.value)}
                                            placeholder="••••••••"
                                        />

                                        <Button
                                            type="submit"
                                            fullWidth
                                            className="!py-3.5 mt-2 shadow-lg"
                                        >
                                            ENTRAR NO PAINEL
                                        </Button>

                                        <div className="text-center pt-2">
                                            <Link
                                                href="/auth/recovery"
                                                className="text-[13px] font-bold text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] transition-colors"
                                            >
                                                Esqueceu a senha?
                                            </Link>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>

                <Footer />
            </div>
        </GuestOnly>
    );
}