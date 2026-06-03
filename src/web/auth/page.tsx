import React, { useState, useEffect, useRef } from 'react';
import { GuestOnly, useSession } from "@vatts/auth/react";
import { useToast } from "@/web/contexts/ToastContext";
import { Link, router, VattsImage } from "vatts/react";
import Input from "@/web/components/commons/components/Input";
import Button from "@/web/components/commons/components/Button";
import Footer from "@/web/components/commons/Footer";
import Card from "@/web/components/commons/components/Card";

// Importando as bibliotecas nativas de Captcha
import { Turnstile } from '@marsidev/react-turnstile';
import HCaptcha from '@hcaptcha/react-hcaptcha';

export default function App() {
    const session = useSession();
    const toast = useToast();

    // Estados do formulário de credenciais
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');

    // Estado para controlar a exibição do botão de OAuth
    const [billingSystem, setBillingSystem] = useState({ active: false, system: null });

    // Estados do Captcha
    const [captchaConfig, setCaptchaConfig] = useState({ active: false, system: 'none', siteKey: '' });
    const [captchaToken, setCaptchaToken] = useState('');

    // Refs para podermos resetar o Captcha programaticamente caso o login falhe
    const turnstileRef = useRef(null);
    const hcaptchaRef = useRef(null);

    const isDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    const urlImage = isDark ? '/assets/img/logo-white.png' : '/assets/img/logo-dark.png';

    useEffect(() => {
        const checkBillingStatus = async () => {
            try {
                const res = await fetch('/api/v1/auth/billing/status');
                if (res.ok) {
                    const data = await res.json();
                    if (data.is_active) {
                        setBillingSystem({ active: true, system: data.system });
                    }
                }
            } catch (error) {
                console.error("Erro ao verificar status do sistema de faturamento:", error);
            }
        };

        const fetchCaptchaConfig = async () => {
            try {
                const res = await fetch('/api/v1/auth/captcha/config');
                if (res.ok) {
                    const data = await res.json();
                    if (data.is_active) {
                        setCaptchaConfig({
                            active: true,
                            system: data.system,
                            siteKey: data.site_key
                        });
                    }
                }
            } catch (error) {
                console.error("Erro ao buscar configurações do Captcha:", error);
            }
        };

        checkBillingStatus();
        fetchCaptchaConfig();
    }, []);

    const handleSubmit = async (e: any) => {
        e.preventDefault();

        // 1. Verifica se o Captcha tá ativo e se o cara resolveu
        if (captchaConfig.active) {
            if (!captchaToken) {
                toast.addToast('Por favor, resolva o captcha antes de entrar.', 'error');
                return;
            }

            // 2. Manda pro SEU endpoint de verify antes do login
            try {
                const verifyRes = await fetch('/api/v1/auth/captcha/validate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ captcha_token: captchaToken })
                });

                const verifyData = await verifyRes.json();

                if (!verifyData.success) {
                    toast.addToast('Captcha inválido ou expirado.', 'error');

                    // Reseta o captcha pro cara tentar de novo
                    setCaptchaToken('');
                    if (captchaConfig.system === 'turnstile' && turnstileRef.current) {
                        // @ts-ignore
                        turnstileRef.current.reset();
                    } else if (captchaConfig.system === 'hcaptcha' && hcaptchaRef.current) {
                        // @ts-ignore
                        hcaptchaRef.current.resetCaptcha();
                    }
                    return; // Para a execução aqui, nem tenta logar
                }
            } catch (error) {
                toast.addToast('Erro de comunicação ao validar o captcha.', 'error');
                return;
            }
        }

        // 3. Agora sim, com o captcha validado (ou se tava desativado), faz o login limpo!
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

            // Se errou a senha, reseta o captcha pra evitar reaproveitamento do token
            setCaptchaToken('');
            if (captchaConfig.system === 'turnstile' && turnstileRef.current) {
                // @ts-ignore
                turnstileRef.current.reset();
            } else if (captchaConfig.system === 'hcaptcha' && hcaptchaRef.current) {
                // @ts-ignore
                hcaptchaRef.current.resetCaptcha();
            }
        }
    };

    const handleOAuthLogin = async () => {
        try {
            await session.signIn(billingSystem.system!!, { popup: true });
        } catch (error) {
            toast.addToast('Erro ao iniciar o login via área do cliente.', 'error');
        }
    };

    return (
        <GuestOnly redirectTo="/">
            <div className="min-h-screen flex flex-col relative bg-[var(--color-background)]">
                <div className="flex-1 flex flex-col justify-center items-center px-4 py-8 animate-[fadeIn_0.4s_ease-out]">

                    <div className="w-full max-w-3xl">
                        <div className="text-center mb-6">
                            <h1 className="text-2xl md:text-3xl font-black tracking-tight text-[var(--color-text-value)]">
                                Autenticação
                            </h1>
                            <p className="text-[var(--color-text-sub)] mt-1 text-sm font-medium">
                                Faça login para acessar o painel de controle
                            </p>
                        </div>

                        <Card>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 items-center p-2 md:p-6">

                                <div className="hidden md:flex justify-center items-center p-4 rounded-xl h-full shadow-inner bg-[var(--color-background-sub)]">
                                    <VattsImage
                                        src={urlImage}
                                        width={240} // Aumentado de 180 para 240
                                        className="hover:scale-105 transition-transform duration-500 drop-shadow-xl"
                                    />
                                </div>

                                <div className="w-full flex flex-col">
                                    <div className="md:hidden flex justify-center mb-6">
                                        <VattsImage src={urlImage} width={180} /> {/* Aumentado de 140 para 180 */}
                                    </div>

                                    <form onSubmit={handleSubmit} className="space-y-4 w-full">
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

                                        {/* Renderização das Libs de Captcha */}
                                        {captchaConfig.active && (
                                            <div className="flex justify-center my-2 scale-90 origin-center">
                                                {captchaConfig.system === 'turnstile' ? (
                                                    <Turnstile
                                                        ref={turnstileRef}
                                                        siteKey={captchaConfig.siteKey}
                                                        onSuccess={(token) => setCaptchaToken(token)}
                                                        options={{ theme: 'dark' }}
                                                    />
                                                ) : (
                                                    <HCaptcha
                                                        ref={hcaptchaRef}
                                                        sitekey={captchaConfig.siteKey}
                                                        onVerify={(token) => setCaptchaToken(token)}
                                                        theme="dark"
                                                    />
                                                )}
                                            </div>
                                        )}

                                        <Button
                                            type="submit"
                                            fullWidth
                                            className="!py-3 mt-1 shadow-md"
                                        >
                                            ENTRAR NO PAINEL
                                        </Button>

                                        {billingSystem.active && (
                                            <div className="mt-4 flex flex-col space-y-3">
                                                <div className="flex items-center before:flex-1 before:border-t before:border-[var(--color-border)] after:flex-1 after:border-t after:border-[var(--color-border)]">
                                                    <p className="text-center text-[var(--color-text-sub)] text-[10px] font-bold uppercase tracking-widest mx-3">
                                                        OU
                                                    </p>
                                                </div>

                                                <Button
                                                    variant={"info"}
                                                    type="button"
                                                    onClick={handleOAuthLogin}
                                                    fullWidth
                                                    className="!py-2.5 text-sm"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="16" height="16" fill="currentColor" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="mr-2 inline"><path d="M64 128C64 92.7 92.7 64 128 64L384 64C419.3 64 448 92.7 448 128L448 272.7C412.3 275.6 379.5 288.3 352 308.1L352 304.1C352 295.3 344.8 288.1 336 288.1L304 288.1C295.2 288.1 288 295.3 288 304.1L288 336.1C288 344.9 295.2 352.1 304 352.1L308 352.1C294.2 371.3 283.9 393.1 277.9 416.6C276 416.2 274 416.1 272 416.1L240 416.1C222.3 416.1 208 430.4 208 448.1L208 528.1L282.9 528.1C289 545.4 297.5 561.5 308 576.1L128 576C92.7 576 64 547.3 64 512L64 128zM176 160C167.2 160 160 167.2 160 176L160 208C160 216.8 167.2 224 176 224L208 224C216.8 224 224 216.8 224 208L224 176C224 167.2 216.8 160 208 160L176 160zM288 176L288 208C288 216.8 295.2 224 304 224L336 224C344.8 224 352 216.8 352 208L352 176C352 167.2 344.8 160 336 160L304 160C295.2 160 288 167.2 288 176zM176 288C167.2 288 160 295.2 160 304L160 336C160 344.8 167.2 352 176 352L208 352C216.8 352 224 344.8 224 336L224 304C224 295.2 216.8 288 208 288L176 288zM320 464C320 384.5 384.5 320 464 320C543.5 320 608 384.5 608 464C608 543.5 543.5 608 464 608C384.5 608 320 543.5 320 464zM460.7 396.7C454.5 402.9 454.5 413.1 460.7 419.3L489.4 448L400 448C391.2 448 384 455.2 384 464C384 472.8 391.2 480 400 480L489.4 480L460.7 508.7C454.5 514.9 454.5 525.1 460.7 531.3C466.9 537.5 477.1 537.5 483.3 531.3L539.3 475.3C545.5 469.1 545.5 458.9 539.3 452.7L483.3 396.7C477.1 390.5 466.9 390.5 460.7 396.7z"/></svg>
                                                    ENTRAR COM {billingSystem.system === 'paymenter' ? 'PAYMENTER' : 'WHMCS'}
                                                </Button>
                                            </div>
                                        )}

                                        <div className="text-center pt-1">
                                            <Link
                                                href="/auth/recovery"
                                                className="text-xs font-bold text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] transition-colors"
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