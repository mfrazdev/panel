import React, { useState, useEffect } from 'react';
import { GuestOnly } from "@vatts/auth/react";
import { useToast } from "@/web/contexts/ToastContext";
import { Link, router, VattsImage } from "vatts/react";
import Input from "@/web/components/commons/components/Input";
import Button from "@/web/components/commons/components/Button";
import Footer from "@/web/components/commons/Footer";
import Card from "@/web/components/commons/components/Card";
import { useSession } from "@vatts/auth/react";

export default function Recovery() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [code, setCode] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const toast = useToast();

    const session = useSession()

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const recoveryCode = params.get('code');

        if (recoveryCode) {
            // Função para validar o código assim que a página carregar
            const validateCode = async () => {
                try {
                    const response = await fetch(`/api/v1/users/recovery/validate?code=${recoveryCode}`);
                    const data = await response.json();

                    if (response.ok && data.success) {
                        // Código válido, define o estado para mostrar o form de nova senha
                        setCode(recoveryCode);
                    } else {
                        // Código inválido ou expirado
                        toast.addToast(data.error || 'Código inválido ou expirado.', 'error');
                        router.push('/auth');
                    }
                } catch (error) {
                    toast.addToast('Erro de conexão ao validar o código.', 'error');
                    router.push('/auth');
                }
            };

            validateCode();
        }
    }, []);

    const handleSendEmail = async (e: { preventDefault: () => void; }) => {
        e.preventDefault();
        setLoading(true);
        try {
            const response = await fetch('/api/v1/users/recovery/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                toast.addToast('E-mail de recuperação enviado! Verifique sua caixa de entrada.', 'success');
                setEmail('');
            } else {
                toast.addToast(data.error || 'Erro ao enviar e-mail de recuperação.', 'error');
            }
        } catch (error) {
            toast.addToast('Ocorreu um erro inesperado ao conectar com o servidor.', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleResetPassword = async (e: { preventDefault: () => void; }) => {
        e.preventDefault();
        setLoading(true);

        if(password !== confirmPassword) {
            toast.addToast('As senhas não coincidem. Por favor, verifique e tente novamente.', 'error');
            setLoading(false);
            return;
        }

        try {
            // Ajustado para '/change' e passando o code via query string para bater com o backend PHP
            const response = await fetch(`/api/v1/users/recovery/change?code=${code}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ password, confirmPassword })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                toast.addToast('Senha redefinida com sucesso!', 'success');
                router.push('/auth');
            } else {
                toast.addToast(data.error || 'Erro ao redefinir a senha. O link pode ter expirado.', 'error');
            }
        } catch (error) {
            toast.addToast('Ocorreu um erro inesperado ao conectar com o servidor.', 'error');
        } finally {
            setLoading(false);
        }
    };
    const isDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    const urlImage = isDark ? '/assets/img/logo-white.png' : '/assets/img/logo-dark.png';
    return (
        <div className="min-h-screen flex flex-col justify-center items-center font-sans relative">
            <h1 className="text-[32px] text-(--color-primary) font-semibold text-center mb-10 tracking-tight">
                {code ? 'Redefinir Senha' : 'Recuperar Senha'}
            </h1>

            <Card>
                <div className="grid grid-cols-[1fr_1.5fr] gap-4">
                    {/* Lado Esquerdo: Logo */}
                    <div className="grid place-items-center p-7">
                        <VattsImage src={urlImage} width={250}/>
                    </div>

                    {/* Lado Direito: Formulário */}
                    <div className="w-full grid place-items-center p-5 pl-8">
                        {!code ? (
                            <form onSubmit={handleSendEmail} className="space-y-6 w-full">
                                <div>
                                    <label className="block text-[11px] font-bold text-(--color-text-label) mb-2 tracking-wider uppercase">
                                        E-mail da sua conta
                                    </label>
                                    <Input
                                        type="email"
                                        value={session.data ? session.data.user.email : email}
                                        readOnly={!!session.data} // Se o usuário estiver logado, o campo de email fica readonly
                                        placeholder={session.data ? session.data.user.email : 'Digite seu e-mail'}
                                        onChange={(e) => setEmail(e.target.value)}
                                        required
                                    />
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full font-bold py-3 px-4 uppercase"
                                    disabled={loading}
                                >
                                    {loading ? 'Enviando...' : 'Enviar Link'}
                                </Button>

                                {!session.data && (
                                    <div className="text-center">
                                        <Link href="/auth" className="text-(--color-text-label) text-[12px] uppercase font-bold">
                                            Voltar para Login
                                        </Link>
                                    </div>
                                )}
                            </form>
                        ) : (
                            <form onSubmit={handleResetPassword} className="space-y-6 w-full">
                                <div>
                                    <label className="block text-[11px] font-bold text-(--color-text-label) mb-2 tracking-wider uppercase">
                                        Nova Senha
                                    </label>
                                    <Input
                                        type="password"
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}
                                        required
                                    />
                                </div>

                                <div>
                                    <label className="block text-[11px] font-bold text-(--color-text-label) mb-2 tracking-wider uppercase">
                                        Confirmar Senha
                                    </label>
                                    <Input
                                        type="password"
                                        value={confirmPassword}
                                        onChange={(e) => setConfirmPassword(e.target.value)}
                                        required
                                    />
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full font-bold py-3 px-4 uppercase"
                                    disabled={loading}
                                >
                                    {loading ? 'Salvando...' : 'Redefinir Senha'}
                                </Button>

                                <div className="text-center">
                                    <Link href="/auth" className="text-(--color-text-label) text-[12px] uppercase font-bold">
                                        Cancelar
                                    </Link>
                                </div>
                            </form>
                        )}
                    </div>
                </div>
            </Card>

            <Footer />
        </div>
    );
}