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
    const [cooldown, setCooldown] = useState(0); // Estado para o contador visual

    const toast = useToast();
    const session = useSession();

    const COOLDOWN_KEY = 'recovery_email_cooldown';

    // 1. Efeito para preencher e-mail se logado e gerenciar o Cooldown do LocalStorage
    useEffect(() => {
        // Preenche e-mail se logado
        if (session.data?.user?.email) {
            setEmail(session.data.user.email);
        }

        // Verifica se há um cooldown ativo no localStorage
        const expiry = localStorage.getItem(COOLDOWN_KEY);
        if (expiry) {
            const remaining = Math.ceil((parseInt(expiry) - Date.now()) / 1000);
            if (remaining > 0) {
                setCooldown(remaining);
            }
        }
    }, [session.data]);

    // 2. Timer do Cooldown
    useEffect(() => {
        if (cooldown > 0) {
            const timer = setTimeout(() => setCooldown(cooldown - 1), 1000);
            return () => clearTimeout(timer);
        }
    }, [cooldown]);

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const recoveryCode = params.get('code');

        if (recoveryCode) {
            const validateCode = async () => {
                try {
                    const response = await fetch(`/api/v1/users/recovery/validate?code=${recoveryCode}`);
                    const data = await response.json();

                    if (response.ok && data.success) {
                        setCode(recoveryCode);
                    } else {
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

        if (cooldown > 0) return;

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

                // Define o cooldown de 60 segundos no estado e no localStorage
                const expiry = Date.now() + 60000;
                localStorage.setItem(COOLDOWN_KEY, expiry.toString());
                setCooldown(60);

                if (!session.data) setEmail('');
            } else {
                // Se o backend retornar 429 (Too Many Requests), também ativamos o cooldown aqui por precaução
                if (response.status === 429) {
                    const expiry = Date.now() + 60000;
                    localStorage.setItem(COOLDOWN_KEY, expiry.toString());
                    setCooldown(60);
                }
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
                    <div className="grid place-items-center p-7">
                        <VattsImage src={urlImage} width={250}/>
                    </div>

                    <div className="w-full grid place-items-center p-5 pl-8">
                        {!code ? (
                            <form onSubmit={handleSendEmail} className="space-y-6 w-full">
                                <div>
                                    <label className="block text-[11px] font-bold text-(--color-text-label) mb-2 tracking-wider uppercase">
                                        E-mail da sua conta
                                    </label>
                                    <Input
                                        type="email"
                                        value={email}
                                        readOnly={!!session.data}
                                        placeholder={'Digite seu e-mail'}
                                        onChange={(e) => setEmail(e.target.value)}
                                        required
                                    />
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full font-bold py-3 px-4 uppercase"
                                    disabled={loading || cooldown > 0}
                                >
                                    {loading ? 'Enviando...' : cooldown > 0 ? `Aguarde ${cooldown}s` : 'Enviar Link'}
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