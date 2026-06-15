import React, { useState, useEffect } from 'react';
import ServerSidebar from './ServerSidebar';
import ConsoleContainer from "./console/ConsoleContainer";
import { useServerContext } from "@/web/contexts/ServerContext";
import { router } from "nytlex/react";
import Footer from "@/web/components/commons/Footer";
import ConfigurationContainer from "@/web/components/server/config/ConfigurationContainer";
import { useLoading } from "@/web/components/wrappers/Wrapper";
import { motion, AnimatePresence } from "framer-motion";
import StartupContainer from "@/web/components/server/startup/StartupContainer";
import FileManagerContainer from "@/web/components/server/filemanager/FileManagerContainer";
import AllocationsContainer from "@/web/components/server/allocations/AllocationsContainer";
import DatabasesContainer from "@/web/components/server/databases/DatabasesContainer";
import SchedulersContainer from "@/web/components/server/schedulers/SchedulersContainer";
import NotFound from "@/web/notFound";
import AuditContainer from "@/web/components/server/audit/AuditContainer";

type ServerProps = {
    action?: string;
}

export default function ServerContainer({ action }: ServerProps) {
    const context = useServerContext();
    const {
        server,
        isLoadingServer,
        connectUsageWs,
        disconnectUsageWs,
        connectConsoleWs,
        disconnectConsoleWs
    } = context;

    if(!isLoadingServer && !server) {
        return <NotFound/>;
    }

    const serverId = server?.serverUuid.split('-')[0] ?? "";
    const pathname = router.pathname;
    const { setLoadingBar } = useLoading();

    const [currentAction, setCurrentAction] = useState(action?.split("/")[0] || 'console');

    // Sincroniza a URL com o estado interno
    useEffect(() => {
        const pathSegments = pathname.split('/').filter(Boolean);
        let actionFromUrl = pathSegments[2] || 'console';

        // Força a aba console e arruma a URL se o servidor estiver suspenso
        if (server?.suspended === 1) {
            actionFromUrl = 'console';
            if (pathSegments[2] && pathSegments[2] !== 'console') {
                window.history.replaceState(null, '', `/server/${serverId}`);
            }
        }

        if (actionFromUrl !== currentAction) {
            setCurrentAction(actionFromUrl);
        }
    }, [pathname, server?.suspended, serverId]);

    // LÓGICA CENTRAL DE CONEXÃO
    useEffect(() => {
        if (!isLoadingServer && server) {
            connectUsageWs();
            connectConsoleWs();
        }
        return () => {
            disconnectUsageWs();
            disconnectConsoleWs();
        };
    }, [isLoadingServer, server, connectUsageWs, connectConsoleWs, disconnectUsageWs, disconnectConsoleWs]);


    // LÓGICA PARA ATUALIZAR O TÍTULO DA PÁGINA
    useEffect(() => {
        if (server) {
            const formattedAction = currentAction.charAt(0).toUpperCase() + currentAction.slice(1);
            document.title = `${server.name} | ${formattedAction}`;
        }
    }, [server, currentAction]);

    const changeAction = (newAction: string) => {
        if (newAction === currentAction) return;

        // Impede a troca de abas se o servidor estiver suspenso
        if (server?.suspended === 1 && newAction !== 'console') {
            return;
        }

        setLoadingBar(true);
        setCurrentAction(newAction);

        const url = newAction === 'console'
            ? `/server/${serverId}`
            : `/server/${serverId}/${newAction}`;

        window.history.pushState(null, '', url);

        setTimeout(() => setLoadingBar(false), 300);
    };

    const renderContent = () => {
        switch (currentAction) {
            case 'console':
                return <ConsoleContainer />;
            case 'files':
                return <FileManagerContainer action={action}/>;
            case 'database':
                return <DatabasesContainer />;
            case 'settings':
                return <ConfigurationContainer />;
            case 'startup':
                return <StartupContainer/>
            case 'allocations':
                return <AllocationsContainer />
            case 'schedulers':
                return <SchedulersContainer></SchedulersContainer>
            case 'audit':
                return <AuditContainer/>
            default:
                return <NotFound/>;
        }
    };

    const component = renderContent()

    return (
        <div className="flex-1 flex flex-row items-start relative w-full">
            {component !== null && !isLoadingServer && (
                <ServerSidebar
                    serverId={serverId}
                    activeTab={currentAction}
                    changeAction={changeAction}
                />
            )}

            {/* A coluna da direita agora segura o Footer, impedindo ele de vazar pra baixo da Sidebar */}
            <div className="flex-1 flex flex-col min-h-[calc(100vh-4rem)] overflow-x-hidden">
                <main className="flex-1">
                    <AnimatePresence mode="wait">
                        <motion.div
                            key={currentAction}
                            initial={{ opacity: 0, x: 5 }}
                            animate={{ opacity: 1, x: 0 }}
                            exit={{ opacity: 0, x: -5 }}
                            transition={{ duration: 0.15, ease: "easeOut" }}
                            className="h-full"
                        >
                            {component}
                        </motion.div>
                    </AnimatePresence>
                </main>

                {/* Footer perfeitamente posicionado no final do conteúdo */}
                <Footer/>
            </div>
        </div>
    );
};