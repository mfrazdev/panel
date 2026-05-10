import React, { useState } from 'react';
import {GuestOnly, useSession} from "@vatts/auth/react";
import {useToast} from "@/web/contexts/ToastContext";
import {Link, router, VattsImage} from "vatts/react";
import Input from "@/web/components/commons/components/Input";
import Button from "@/web/components/commons/components/Button";
import Footer from "@/web/components/commons/Footer";
import Card from "@/web/components/commons/components/Card";

export default function App() {
    const session = useSession()
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const toast = useToast()
    const isDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    const urlImage = isDark ? '/assets/img/logo-white.png' : '/assets/img/logo-dark.png';
    const handleSubmit = async (e: { preventDefault: () => void; }) => {
        e.preventDefault();
        const sign = await session.signIn('credentials', {
            email: username,
            password: password,
            redirect: false
        })
        if (sign && sign.ok === true) {
            toast.addToast('Login realizado com sucesso!', 'success')
            router.push('/')
        } else {
            toast.addToast('Erro ao realizar login. Verifique suas credenciais.', 'error')
        }
    };

    return (
        <GuestOnly redirectTo="/">
            <div
                className="min-h-screen flex flex-col justify-center items-center font-sans relative"
            >
                <h1 className="text-[32px] text-(--color-primary) font-semibold text-center mb-10 tracking-tight">
                    Autenticação
                </h1>
                <Card>
                    <div className="grid grid-cols-[1fr_1.5fr] gap-4">
                        <div className="grid place-items-center p-7">
                            <VattsImage src={urlImage} width={250}/>
                        </div>
                        <div className="w-full grid place-items-center p-5 pl-8">
                            <form onSubmit={handleSubmit} className="space-y-6 w-full">
                                <div>
                                    <label className="block text-[11px] font-bold text-(--color-text-label) mb-2 tracking-wider uppercase">
                                        Nome de Usuário ou Email
                                    </label>
                                    <Input
                                        type="text"
                                        value={username}
                                        onChange={(e) => setUsername(e.target.value)}
                                    />
                                </div>

                                <div>
                                    <label className="block text-[11px] font-bold text-(--color-text-label) mb-2 tracking-wider uppercase">
                                        Senha
                                    </label>
                                    <Input
                                        type="password"
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}
                                    />
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full font-bold py-3 px-4 uppercase"
                                >
                                    Login
                                </Button>


                                <div className="text-center">
                                    <Link
                                        href="/auth/recovery"
                                        className="text-(--color-text-label)"
                                    >
                                        Esqueceu a senha?
                                    </Link>
                                </div>
                            </form>

                        </div>
                    </div>
                </Card>

                {/* Footer */}
                <Footer></Footer>
            </div>
        </GuestOnly>

    );
}