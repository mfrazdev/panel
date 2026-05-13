import React, {createContext, useContext, useEffect, useState, useMemo} from "react";
import {useServerContext} from "@/web/contexts/ServerContext";
import {useSession} from "@vatts/auth/react";

export const ROOT_PATH = "/home/container";

export interface UploadProgressState {
    [taskId: string]: {
        fileName: string;
        progress: number;
        loaded: number;
        total: number;
        status: 'uploading' | 'completed' | 'error';
    };
}

interface FileManagerContextType {
    currentPath: string;
    setCurrentPath: (path: string) => void;
    isEditOpen: boolean;
    setIsEditOpen: (open: boolean) => void;
    editingFilePath: string | null;
    setEditingFilePath: (path: string | null) => void;
    serverId: string;
    navigateToPath: (path: string) => void;
    navigateToEdit: (filePath: string) => void;
    closeEdit: () => void;
    getBreadcrumbs: (path: string) => { name: string; path: string; isBase?: boolean }[];

    // Upload State Methods
    uploadState: UploadProgressState;
    totalUploadProgress: number;
    clearUploads: () => void;

    // API Methods
    listFiles: (path?: string) => Promise<any>;
    readFile: (filePath: string) => Promise<any>;
    writeFile: (filePath: string, content: string) => Promise<any>;
    renameItem: (filePath: string, newName: string) => Promise<any>;
    createDirectory: (dirPath: string) => Promise<any>;
    moveItem: (fromPath: string, toPath: string) => Promise<any>;
    deleteItems: (paths: string[]) => Promise<any>;
    archiveItems: (paths: string[]) => Promise<any>;
    unarchiveItem: (archivePath: string, destination?: string) => Promise<any>;
    downloadFile: (filePath: string) => void;
    uploadFile: (dirPath: string, file: File) => Promise<any>;
}

const FileManagerContext = createContext<FileManagerContextType | undefined>(undefined);

export function FileManagerProvider({ children }: { children: React.ReactNode }) {

    const server = useServerContext();
    const user = useSession();
    const API_BASE_URL = server?.nodeUrl
        ? `${server.nodeUrl}/api/v1/servers/filemanager`
        : "";
    const serverId = server?.server?.serverUuid || "";

    const userUuid = user.data?.user.id;

    const [currentPath, setCurrentPath] = useState(ROOT_PATH);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [editingFilePath, setEditingFilePath] = useState<string | null>(null);

    // Estado de rastreamento de upload
    const [uploadState, setUploadState] = useState<UploadProgressState>({});

    const totalUploadProgress = useMemo(() => {
        const tasks = Object.values(uploadState);
        if (tasks.length === 0) return 0;
        let totalLoaded = 0;
        let totalSize = 0;
        tasks.forEach(task => {
            totalLoaded += task.loaded;
            totalSize += task.total;
        });
        return totalSize === 0 ? 0 : Math.round((totalLoaded / totalSize) * 100);
    }, [uploadState]);

    const clearUploads = () => setUploadState({});

    // ==========================================
    // LÓGICA DE NAVEGAÇÃO E ESTADO (MANTIDA)
    // ==========================================

    useEffect(() => {
        const syncState = () => {
            const hash = window.location.hash;
            if (!hash || hash === "#/" || hash === "#") {
                setCurrentPath(ROOT_PATH);
                setIsEditOpen(false);
                setEditingFilePath(null);
            } else if (hash.startsWith("#edit:")) {
                const path = hash.replace("#edit:", "");
                if (!path || path === "/") {
                    setCurrentPath(ROOT_PATH);
                    setIsEditOpen(false);
                    setEditingFilePath(null);
                    window.history.replaceState({}, "", "#/");
                } else {
                    setIsEditOpen(true);
                    setEditingFilePath(`${ROOT_PATH}/${path.replace(/^\//, "")}`);
                }
            } else {
                const path = hash.replace(/^#\//, "").replace(/^#/, "");
                setCurrentPath(`${ROOT_PATH}/${path}`);
                setIsEditOpen(false);
                setEditingFilePath(null);
            }
        };

        syncState();
        window.addEventListener("popstate", syncState);
        return () => window.removeEventListener("popstate", syncState);
    }, []);

    const navigateToPath = (path: string) => {
        // Garante que não teremos barras duplas soltas caso o caminho seja modificado bruscamente
        const cleanPath = path.replace(/\/+/g, '/').replace(/\/$/, "");

        // Se o cara tentar voltar antes do ROOT (ex: ..), travamos na raiz.
        const safePath = cleanPath.startsWith(ROOT_PATH) ? cleanPath : ROOT_PATH;

        const relativePath = safePath.replace(ROOT_PATH, "");
        const formattedPath = relativePath.startsWith("/") ? relativePath : `/${relativePath}`;
        const hash = formattedPath === "/" || formattedPath === "" ? "/" : formattedPath;

        setCurrentPath(safePath);
        setIsEditOpen(false);
        setEditingFilePath(null);
        window.history.pushState({}, "", `#${hash}`);
    };

    const navigateToEdit = (filePath: string) => {
        // Usando regex para arrancar o home/container com ou sem barra
        const relativePath = filePath.replace(/^\/?home\/container/, "");
        const formattedPath = relativePath.startsWith("/") ? relativePath : `/${relativePath}`;
        const hash = `edit:${formattedPath}`;

        setIsEditOpen(true);
        setEditingFilePath(filePath);
        window.history.pushState({}, "", `#${hash}`);
    };

    const closeEdit = () => {
        setIsEditOpen(false);
        setEditingFilePath(null);
        // Oculta o editor, mas mantém o cara na pasta onde ele estava editando o arquivo.
        const relativePath = currentPath.replace(ROOT_PATH, "");
        const formattedPath = relativePath.startsWith("/") ? relativePath : `/${relativePath}`;
        const hash = formattedPath === "/" || formattedPath === "" ? "/" : formattedPath;

        window.history.pushState({}, "", `#${hash}`);
    };

    const getBreadcrumbs = (path: string) => {
        if (!path) return [];
        const segments = path.split("/").filter(Boolean);
        const breadcrumbs: { name: string; path: string; isBase?: boolean }[] = [];

        // O ROOT_PATH geralmente resulta em ['home', 'container']
        if (segments.length >= 2 && segments[0] === "home" && segments[1] === "container") {
            breadcrumbs.push({
                name: "home / container",
                path: ROOT_PATH,
                isBase: true
            });

            let currentAccumulatedPath = ROOT_PATH;
            for (let i = 2; i < segments.length; i++) {
                currentAccumulatedPath += `/${segments[i]}`;
                breadcrumbs.push({
                    name: segments[i],
                    path: currentAccumulatedPath, // Isso garante que o path gerado pra subpastas seja tipo "/home/container/www/conf"
                });
            }
        } else {
            let currentAccumulatedPath = "";
            segments.forEach((segment) => {
                currentAccumulatedPath += `/${segment}`;
                breadcrumbs.push({
                    name: segment,
                    path: currentAccumulatedPath,
                });
            })
        }

        return breadcrumbs;
    };

    // ==========================================
    // INTEGRAÇÃO COM A API
    // ==========================================

    const formatApiPath = (frontendPath: string) => {
        // Remove agressivamente o prefixo /home/container, garantindo que não vá para a API
        let stripped = frontendPath.replace(/^\/?home\/container/, "");
        
        // Remove a barra inicial se existir (deixa como raiz vazia caso seja apenas /)
        return stripped.startsWith("/") ? stripped.slice(1) : stripped;
    };

    const apiCall = async (endpoint: string, payload: any = {}) => {
        if (!API_BASE_URL) {
            console.warn("Aguardando IP da Node...");
            return { items: [] };
        }
        const response = await fetch(`${API_BASE_URL}/${endpoint}`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                userUuid,
                serverId,
                disk: server.server?.disk || 1024,
                ...payload
            })
        });

        const textData = await response.text();

        if (!response.ok) {
            let errorData;

            try {
                errorData = JSON.parse(textData);
            } catch {
                errorData = textData;
            }

            console.error("Erro completo:", {
                status: response.status,
                statusText: response.statusText,
                body: errorData
            });

            throw new Error(`Erro ${response.status}: ${typeof errorData === 'object' ? JSON.stringify(errorData) : errorData}`);
        }

        return JSON.parse(textData)
    };

    const listFiles = async (path: string = currentPath) => {
        return apiCall("list", { path: formatApiPath(path) });
    };

    const readFile = async (filePath: string) => {
        return apiCall("read", { path: formatApiPath(filePath) });
    };

    const writeFile = async (filePath: string, content: string) => {
        return apiCall("write", { path: formatApiPath(filePath), content });
    };

    const renameItem = async (filePath: string, newName: string) => {
        return apiCall("rename", { path: formatApiPath(filePath), newName });
    };

    const createDirectory = async (dirPath: string) => {
        return apiCall("mkdir", { path: formatApiPath(dirPath) });
    };

    const moveItem = async (fromPath: string, toPath: string) => {
        return apiCall("move", {
            from: formatApiPath(fromPath),
            to: formatApiPath(toPath)
        });
    };

    const deleteItems = async (paths: string[]) => {
        const formattedPaths = paths.map(formatApiPath);
        return apiCall("mass", { action: "delete", paths: formattedPaths });
    };

    const archiveItems = async (paths: string[]) => {
        const formattedPaths = paths.map(formatApiPath);
        return apiCall("mass", { action: "archive", paths: formattedPaths });
    };

    const unarchiveItem = async (archivePath: string, destination?: string) => {
        return apiCall("unarchive", {
            path: formatApiPath(archivePath),
            destination: destination ? formatApiPath(destination) : undefined
        });
    };

    const downloadFile = (filePath: string) => {
        // Como agora será um GET, mandamos as informações na URL (Query String)
        const params = new URLSearchParams({
            userUuid: userUuid.toString(),
            serverId: serverId,
            path: formatApiPath(filePath)
            // O disk não é necessário para download, já que não escrevemos nada no disco.
        });

        const downloadUrl = `${API_BASE_URL}/download?${params.toString()}`;

        // Cria um link e clica nele dinamicamente.
        // Isso inicia o download nativo do navegador na hora, sem carregar na memória (Blob).
        const link = document.createElement('a');
        link.href = downloadUrl;
        // Opcional, mas ajuda a dizer pro navegador "baixe, não abra na aba"
        link.setAttribute('download', '');
        document.body.appendChild(link);
        link.click();
        link.remove();
    };

    const uploadFile = async (dirPath: string, file: File) => {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append("serverId", serverId);
            if (userUuid) formData.append("userUuid", userUuid);
            formData.append('disk', server.server?.disk ? server.server.disk.toString() : "1024");
            const relativeDir = formatApiPath(dirPath);
            const fullUploadPath = relativeDir ? `${relativeDir}/${file.name}` : file.name;

            formData.append("path", fullUploadPath);
            formData.append("file", file);

            // Inicia o rastreamento desse arquivo
            const taskId = fullUploadPath;
            setUploadState(prev => ({
                ...prev,
                [taskId]: {
                    fileName: file.name,
                    progress: 0,
                    loaded: 0,
                    total: file.size,
                    status: 'uploading'
                }
            }));

            // Usando XHR porque a API fetch nativa não suporta rastrear o progresso do upload ainda
            const xhr = new XMLHttpRequest();
            xhr.open("POST", `${API_BASE_URL}/upload`);

            xhr.upload.onprogress = (event) => {
                if (event.lengthComputable) {
                    const progress = Math.round((event.loaded / event.total) * 100);
                    setUploadState(prev => ({
                        ...prev,
                        [taskId]: {
                            ...prev[taskId],
                            progress,
                            loaded: event.loaded,
                            total: event.total
                        }
                    }));
                }
            };

            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    setUploadState(prev => ({
                        ...prev,
                        [taskId]: {
                            ...prev[taskId],
                            progress: 100,
                            loaded: file.size,
                            total: file.size,
                            status: 'completed'
                        }
                    }));
                    try {
                        resolve(JSON.parse(xhr.responseText));
                    } catch {
                        resolve({ status: 'ok' });
                    }
                } else {
                    setUploadState(prev => ({
                        ...prev,
                        [taskId]: { ...prev[taskId], status: 'error' }
                    }));
                    reject(new Error(`Falha no upload: ${xhr.statusText || xhr.status}`));
                }
            };

            xhr.onerror = () => {
                setUploadState(prev => ({
                    ...prev,
                    [taskId]: { ...prev[taskId], status: 'error' }
                }));
                reject(new Error("Erro de rede ao fazer upload"));
            };

            xhr.send(formData);
        });
    };

    return (
        <FileManagerContext.Provider value={{
            currentPath,
            setCurrentPath,
            isEditOpen,
            setIsEditOpen,
            editingFilePath,
            setEditingFilePath,
            serverId,
            navigateToPath,
            navigateToEdit,
            closeEdit,
            getBreadcrumbs,

            // Novos exports de estado
            uploadState,
            totalUploadProgress,
            clearUploads,

            // Exports da API
            listFiles,
            readFile,
            writeFile,
            renameItem,
            createDirectory,
            moveItem,
            deleteItems,
            archiveItems,
            unarchiveItem,
            downloadFile,
            uploadFile
        }}>
            {children}
        </FileManagerContext.Provider>
    );
}

export function useFileManager() {
    const context = useContext(FileManagerContext);
    if (!context) {
        throw new Error("useFileManager must be used within a FileManagerProvider");
    }
    return context;
}