import React from "react";
import { TriangleAlert } from "lucide-react";
import Card from "@/web/components/commons/components/Card";
import { Link } from "vatts/react";
import Footer from "@/web/components/commons/Footer";
import Button from "@/web/components/commons/components/Button";

export default function NotFound() {
    return (
        <div className="min-h-screen flex flex-col relative bg-[var(--color-background)]">
            {/* flex-1 empurra o Footer para a base da tela */}
            <div className="flex-1 flex flex-col justify-center items-center px-4 py-12 animate-[fadeIn_0.4s_ease-out]">
                <div className="w-full max-w-md">
                    <Card>
                        <div className="flex flex-col items-center text-center p-6 md:p-8">
                            <div className="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-[var(--color-danger)]/10 border border-[var(--color-danger)]/20 shadow-sm">
                                <TriangleAlert className="h-10 w-10 text-[var(--color-danger)]" />
                            </div>

                            <h1 className="text-5xl font-black tracking-tight text-[var(--color-text-value)]">
                                404
                            </h1>

                            <p className="mt-3 text-xl font-bold text-[var(--color-text-label)]">
                                Página não encontrada
                            </p>

                            <p className="mt-3 text-[14px] font-medium leading-relaxed text-[var(--color-text-sub)]">
                                A página que você tentou acessar não existe, foi removida
                                ou o link está quebrado.
                            </p>

                            <a href="/">
                                <Button className="mt-8 !py-3.5 !px-8 shadow-lg">
                                    VOLTAR PARA O INÍCIO
                                </Button>
                            </a>
                        </div>
                    </Card>
                </div>
            </div>

            {/* Footer perfeitamente posicionado sem margens negativas */}
            <Footer />
        </div>
    );
}