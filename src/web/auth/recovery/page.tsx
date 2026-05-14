import React, { useState, useEffect } from 'react';
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

    const handleSendEmail = async (e: React.FormEvent) => {
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

    const handleResetPassword = async (e: React.FormEvent) => {
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
        <div className="min-h-screen flex flex-col relative bg-[var(--color-background)]">

            {/* flex-1 garante que o conteúdo central empurre o footer lá para baixo */}
            <div className="flex-1 flex flex-col justify-center items-center px-4 py-12 animate-[fadeIn_0.4s_ease-out]">
                <div className="w-full max-w-4xl">

                    {/* Título fora do card para dar respiro */}
                    <div className="text-center mb-10">
                        <h1 className="text-3xl md:text-4xl font-black tracking-tight text-[var(--color-text-value)]">
                            {code ? 'Redefinir Senha' : 'Recuperar Senha'}
                        </h1>
                        <p className="text-[var(--color-text-sub)] mt-2 font-medium">
                            {code ? 'Crie uma nova senha segura para a sua conta' : 'Enviaremos um link de recuperação para o seu e-mail'}
                        </p>
                    </div>

                    <Card>
                        {/* Ajustado para ser 1 coluna no mobile, e 2 no Desktop */}
                        <div className="grid grid-cols-1 md:grid-cols-[1fr_1.5fr] gap-6 md:gap-10 items-center">

                            {/* Lado Esquerdo - Logo (Escondido no mobile) */}
                            <div className="hidden md:flex justify-center items-center p-8 rounded-xl h-full shadow-inner">
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

                                {!code ? (
                                    <form onSubmit={handleSendEmail} className="space-y-6 w-full">
                                        <Input
                                            label="E-mail da sua conta"
                                            type="email"
                                            value={email}
                                            readOnly={!!session.data}
                                            placeholder="Digite seu e-mail"
                                            onChange={(e) => setEmail(e.target.value)}
                                            required
                                        />

                                        <Button
                                            type="submit"
                                            fullWidth
                                            disabled={loading || cooldown > 0}
                                            className="!py-3.5 mt-2 shadow-lg"
                                        >
                                            {loading ? 'Enviando...' : cooldown > 0 ? `Aguarde ${cooldown}s` : 'Enviar Link'}
                                        </Button>

                                        {!session.data && (
                                            <div className="text-center pt-2">
                                                <Link href="/auth" className="text-[13px] font-bold text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] transition-colors uppercase">
                                                    Voltar para Login
                                                </Link>
                                            </div>
                                        )}
                                    </form>
                                ) : (
                                    <form onSubmit={handleResetPassword} className="space-y-6 w-full">
                                        <Input
                                            label="Nova Senha"
                                            type="password"
                                            value={password}
                                            placeholder="••••••••"
                                            onChange={(e) => setPassword(e.target.value)}
                                            required
                                        />

                                        <Input
                                            label="Confirmar Senha"
                                            type="password"
                                            value={confirmPassword}
                                            placeholder="••••••••"
                                            onChange={(e) => setConfirmPassword(e.target.value)}
                                            required
                                        />

                                        <Button
                                            type="submit"
                                            fullWidth
                                            disabled={loading}
                                            className="!py-3.5 mt-2 shadow-lg"
                                        >
                                            {loading ? 'Salvando...' : 'Redefinir Senha'}
                                        </Button>

                                        <div className="text-center pt-2">
                                            <Link href="/auth" className="text-[13px] font-bold text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] transition-colors uppercase">
                                                Cancelar
                                            </Link>
                                        </div>
                                    </form>
                                )}
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <Footer />
        </div>
    );
}