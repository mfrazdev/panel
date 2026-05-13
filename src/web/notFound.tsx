import { TriangleAlert } from "lucide-react";
import Card from "@/web/components/commons/components/Card";
import {Link} from "vatts/react";
import Footer from "@/web/components/commons/Footer";

export default function NotFound() {
    return (
        <>
            <div className="min-h-screen flex justify-center items-start pt-20 px-4">
                <Card className="w-full max-w-md">
                    <div className="flex flex-col items-center text-center p-5">
                        <div className="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-(--color-danger)/10">
                            <TriangleAlert className="h-10 w-10 text-(--color-danger)" />
                        </div>

                        <h1 className="text-5xl font-black tracking-tight text-(--color-text-label)">
                            404
                        </h1>

                        <p className="mt-3 text-xl font-semibold text-(--color-text-label)">
                            Página não encontrada
                        </p>

                        <p className="mt-2 text-sm leading-relaxed text-(--color-text-label)">
                            A página que você tentou acessar não existe, foi removida
                            ou o link está quebrado.
                        </p>

                        <Link
                            href="/"
                            className="mt-8 inline-flex items-center justify-center rounded-md bg-(--color-primary) px-5 py-3 text-sm font-semibold text-(--color-text-label) transition hover:scale-105 hover:bg-(--color-primary)/50"
                        >
                            Voltar para o início
                        </Link>
                    </div>
                </Card>

            </div>
            <div className="-mt-37.5">
                <Footer/>
            </div>
        </>
    );
}