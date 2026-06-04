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

// ==========================================
// COMPONENTE ISOLADO DAS PARTÍCULAS
// Isso garante que o useEffect só rode quando o canvas existir de verdade no DOM
// ==========================================
const ParticleCanvas = () => {
    const canvasRef = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        let animationFrameId: number;
        let particles: any[] = [];
        let isRunning = true;

        let mouse = { x: -1000, y: -1000 };

        const handleMouseMove = (e: MouseEvent) => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
        };

        const handleMouseOut = () => {
            mouse.x = -1000;
            mouse.y = -1000;
        };

        class Particle {
            x: number;
            y: number;
            vx: number;
            vy: number;
            radius: number;

            constructor(x: number, y: number) {
                this.x = x;
                this.y = y;
                this.vx = (Math.random() - 0.5) * 1.5;
                this.vy = (Math.random() - 0.5) * 1.5;
                this.radius = Math.random() * 1.5 + 1;
            }

            draw() {
                if (!ctx) return;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(156, 59, 246, 0.6)';
                ctx.fill();
            }

            update() {
                this.x += this.vx;
                this.y += this.vy;

                if (this.x < 0 || this.x > canvas!.width) this.vx = -this.vx;
                if (this.y < 0 || this.y > canvas!.height) this.vy = -this.vy;

                let dx = mouse.x - this.x;
                let dy = mouse.y - this.y;
                let distance = Math.sqrt(dx * dx + dy * dy);

                const mouseRadius = 250;

                if (distance < mouseRadius) {
                    let forceDirectionX = dx / distance;
                    let forceDirectionY = dy / distance;
                    let force = (mouseRadius - distance) / mouseRadius;

                    this.x -= forceDirectionX * force * 20;
                    this.y -= forceDirectionY * force * 20;
                }

                this.draw();
            }
        }

        const initParticles = () => {
            if (!canvas) return;
            particles = [];

            // Troquei de 10000 para 4000.
            // Se quiser mais ainda, baixe para 3000 ou 2000 (só cuidado pra não travar o PC da galera kkk)
            let numberOfParticles = Math.floor((canvas.width * canvas.height) / 7000);

            for (let i = 0; i < numberOfParticles; i++) {
                particles.push(new Particle(Math.random() * canvas.width, Math.random() * canvas.height));
            }
        };

        const resizeCanvas = () => {
            if (!canvas) return;
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            initParticles();
        };

        const connectParticles = () => {
            for (let a = 0; a < particles.length; a++) {
                for (let b = a; b < particles.length; b++) {
                    let dx = particles[a].x - particles[b].x;
                    let dy = particles[a].y - particles[b].y;
                    let distance = dx * dx + dy * dy;

                    if (distance < 15000) {
                        let opacity = 1 - distance / 15000;
                        ctx.strokeStyle = `rgba(156, 59, 246, ${opacity * 0.3})`;
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.moveTo(particles[a].x, particles[a].y);
                        ctx.lineTo(particles[b].x, particles[b].y);
                        ctx.stroke();
                    }
                }
            }
        };

        const animate = () => {
            if (!isRunning || !ctx || !canvas) return;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < particles.length; i++) {
                particles[i].update();
            }
            connectParticles();
            animationFrameId = requestAnimationFrame(animate);
        };

        resizeCanvas();
        animate();

        const safetyTimeout = setTimeout(() => {
            if (isRunning) resizeCanvas();
        }, 200);

        window.addEventListener('resize', resizeCanvas);
        window.addEventListener('mousemove', handleMouseMove);
        window.addEventListener('mouseout', handleMouseOut);

        return () => {
            isRunning = false;
            clearTimeout(safetyTimeout);
            window.removeEventListener('resize', resizeCanvas);
            window.removeEventListener('mousemove', handleMouseMove);
            window.removeEventListener('mouseout', handleMouseOut);
            cancelAnimationFrame(animationFrameId);
        };
    }, []);

    return (
        <canvas
            ref={canvasRef}
            className="absolute inset-0 z-0 pointer-events-none w-full h-full block"
        />
    );
};

export default function App() {
    const session = useSession();
    const toast = useToast();

    // Estados do formulário de credenciais
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [isLoggingIn, setIsLoggingIn] = useState(false);

    // Estado para controlar a exibição do botão de OAuth
    const [billingSystem, setBillingSystem] = useState({ active: false, system: null });

    // Estados do Captcha
    const [captchaConfig, setCaptchaConfig] = useState({ active: false, system: 'none', siteKey: '' });
    const [captchaToken, setCaptchaToken] = useState('');

    // Refs
    const turnstileRef = useRef<any>(null);
    const hcaptchaRef = useRef<any>(null);

    const isDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    const urlImage = isDark ? '/assets/img/logo-white.png' : '/assets/img/logo-dark.png';

    // ==========================================
    // LÓGICA DE LOGIN (Intacta)
    // ==========================================
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

    const resetCaptcha = () => {
        setCaptchaToken('');
        if (captchaConfig.system === 'turnstile' && turnstileRef.current) {
            turnstileRef.current.reset();
        } else if (captchaConfig.system === 'hcaptcha' && hcaptchaRef.current) {
            hcaptchaRef.current.resetCaptcha();
        }
    };

    const performLogin = async (tokenParaValidar: string) => {
        setIsLoggingIn(true);

        if (captchaConfig.active) {
            try {
                const verifyRes = await fetch('/api/v1/auth/captcha/validate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ captcha_token: tokenParaValidar })
                });

                const verifyData = await verifyRes.json();

                if (!verifyData.success) {
                    toast.addToast('Captcha inválido ou expirado.', 'error');
                    resetCaptcha();
                    setIsLoggingIn(false);
                    return;
                }
            } catch (error) {
                toast.addToast('Erro de comunicação ao validar o captcha.', 'error');
                resetCaptcha();
                setIsLoggingIn(false);
                return;
            }
        }

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
            resetCaptcha();
            setIsLoggingIn(false);
        }
    };

    const handleSubmit = async (e: any) => {
        e.preventDefault();

        if (captchaConfig.active && !captchaToken) {
            if (captchaConfig.system === 'turnstile' && turnstileRef.current) {
                turnstileRef.current.execute();
            } else if (captchaConfig.system === 'hcaptcha' && hcaptchaRef.current) {
                hcaptchaRef.current.execute();
            }
            return;
        }

        await performLogin(captchaToken);
    };

    const handleCaptchaSuccess = (token: string) => {
        setCaptchaToken(token);
        performLogin(token);
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
            {/* Fundo Gradiente Principal */}
            <div className="min-h-screen flex flex-col relative bg-gradient-to-br from-[#000000] via-[#09090b] to-[#120a1f] overflow-hidden">

                {/* Aqui entra o componente isolado que resolve o erro do F5 */}
                <ParticleCanvas />

                <div className="flex-1 flex flex-col justify-center items-center px-4 py-8 animate-[fadeIn_0.4s_ease-out] z-10 relative">

                    <div className="w-full max-w-3xl">


                        <Card>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 items-center p-2 md:p-6">

                                <div className="hidden md:flex justify-center items-center p-4 rounded-xl h-full shadow-inner bg-[var(--color-background-sub)]">
                                    <VattsImage
                                        src={urlImage}
                                        width={240}
                                        className="hover:scale-105 transition-transform duration-500 drop-shadow-xl"
                                    />
                                </div>

                                <div className="w-full flex flex-col">
                                    <div className="md:hidden flex justify-center mb-6">
                                        <VattsImage src={urlImage} width={180} />
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

                                        {captchaConfig.active && (
                                            <div className="hidden">
                                                {captchaConfig.system === 'turnstile' ? (
                                                    <Turnstile
                                                        ref={turnstileRef}
                                                        siteKey={captchaConfig.siteKey}
                                                        onSuccess={handleCaptchaSuccess}
                                                        options={{ theme: 'dark' }}
                                                    />
                                                ) : (
                                                    <HCaptcha
                                                        ref={hcaptchaRef}
                                                        sitekey={captchaConfig.siteKey}
                                                        onVerify={handleCaptchaSuccess}
                                                        theme="dark"
                                                    />
                                                )}
                                            </div>
                                        )}

                                        <Button
                                            type="submit"
                                            fullWidth
                                            disabled={isLoggingIn}
                                            className=" mt-1"
                                        >
                                            {isLoggingIn ? "AUTENTICANDO..." : "ENTRAR NO PAINEL"}
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

                <div className="z-10 relative">
                    <Footer />
                </div>
            </div>
        </GuestOnly>
    );
}