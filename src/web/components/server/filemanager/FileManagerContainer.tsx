import React, { useState, useRef, useEffect, useCallback } from "react";
import Button from "@/web/components/commons/components/Button";

import {
    Folder, FileText, Pencil,
    ArrowRightLeft, X, Check, CloudUpload
} from "lucide-react";

import FileEditContainer from "@/web/components/server/filemanager/FileEdit";
import CreateDirModal from "./modals/CreateDirModal";
import RenameModal from "./modals/RenameModal";
import MoveModal from "./modals/MoveModal";
import CreateFileModal from "./modals/CreateFileModal";
import { useServerContext } from "@/web/contexts/ServerContext";
import { FileManagerProvider, useFileManager } from "./FileManagerContext";
import FileDropdown from "./Dropdown";
import { useLoading } from "@/web/components/wrappers/Wrapper";
import LoadingPage from "@/web/components/commons/LoadingPage";
import {AnimatePresence, motion} from "framer-motion";

// === TIPAGENS ===
type FileItem = {
    name: string;
    type: "folder" | "file";
    size: string;
    lastModified: string;
    rawPath: string;
};

// === FUNÇÕES AUXILIARES ===
const isEditable = (name: string) => {
    if (!name) return false;

    // Extensões clássicas de texto, código, dados e configurações
    const hasEditableExtension = /\.(txt|json|yml|yaml|properties|js|ts|jsx|tsx|sh|bat|cmd|ps1|xml|ini|csv|html|htm|css|scss|sass|less|md|py|rb|php|go|rs|java|c|cpp|h|cs|sql|toml|conf|config|cfg|log|vue|svelte|env)$/i.test(name);

    // Arquivos específicos que geralmente são texto mas não caem no regex acima (ex: .env, Dockerfile)
    const isSpecificTextFile = /^(Dockerfile|\.env.*|\.gitignore|\.npmrc|\.prettierrc|\.eslintrc)$/i.test(name);

    return hasEditableExtension || isSpecificTextFile;
};
const formatBytes = (bytes: number | null) => {
    if (bytes === null) return "--";
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB", "TB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

const formatDate = (timestamp: number) => {
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }).format(new Date(timestamp));
};

// Checkbox maior (w-5 h-5), sem bordas iniciais, e com o "V" interno ajustado pro novo tamanho
const customCheckboxClass = "appearance-none w-5 h-5 rounded-[4px] border-none bg-[var(--color-sidebar)] hover:bg-white/10 checked:bg-[var(--color-primary)] checked:hover:bg-[var(--color-primary)] cursor-pointer flex-shrink-0 relative transition-all shadow-sm before:content-[''] checked:before:block before:hidden before:absolute before:left-[6px] before:top-[2px] before:w-[6px] before:h-[11px] before:border-solid before:border-white before:border-r-[2px] before:border-b-[2px] before:rotate-45";

interface FileManagerProps {
    action?: string;
}

function FileManagerInner({ action = "" }: FileManagerProps) {
    const server = useServerContext();
    const { setLoadingBar } = useLoading();

    const {
        currentPath,
        isEditOpen,
        navigateToPath,
        navigateToEdit,
        getBreadcrumbs,
        listFiles,
        uploadFile,
        deleteItems,
        archiveItems,
        uploadState,
        totalUploadProgress,
        clearUploads
    } = useFileManager();

    const [files, setFiles] = useState<FileItem[]>([]);
    const [selectedFiles, setSelectedFiles] = useState<string[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [hasError, setHasError] = useState(false); // <-- Estado de erro adicionado

    const [isCreateDirOpen, setIsCreateDirOpen] = useState(false);
    const [isCreateFileOpen, setIsCreateFileOpen] = useState(false);
    const [renameTarget, setRenameTarget] = useState<string | null>(null);
    const [moveTargets, setMoveTargets] = useState<string[] | null>(null);
    const [isUploadModalOpen, setIsUploadModalOpen] = useState(false);

    // Upload States
    const [uploadTarget, setUploadTarget] = useState<string | null>(null);
    const [dragOverPath, setDragOverPath] = useState<string | null>(null);

    const fileInputRef = useRef<HTMLInputElement>(null);
    const uploadTasks = Object.values(uploadState);
    const breadcrumbs = getBreadcrumbs(currentPath);
    const rootPath = breadcrumbs.length > 0 ? breadcrumbs[0].path : "";

    const loadingProgress = useLoading()

    useEffect(() => {
        const hasTasks = uploadTasks.length > 0;
        const allTasksFinished = hasTasks && uploadTasks.every(t => t.status === 'completed' || t.status === 'error');

        if (allTasksFinished) {
            const timer = setTimeout(() => {
                setIsUploadModalOpen(false);
                clearUploads();
            }, 1000);
            return () => clearTimeout(timer);
        }
    }, [uploadTasks, clearUploads]);

// Carregar arquivos da pasta atual
    const fetchFiles = useCallback(async () => {
        const start = Date.now();

        loadingProgress.setLoadingBar(true);
        setIsLoading(true);
        setHasError(false); // Reseta o erro ao tentar buscar novamente
        setLoadingBar(true);

        try {
            const data = await listFiles(currentPath);

            const formattedFiles = (data.items || []).map((item: any) => ({
                name: item.name,
                type: item.type,
                size: formatBytes(item.size),
                lastModified: formatDate(item.lastModified),
                // CORREÇÃO: Usando currentPath para concatenar o caminho correto em subpastas!
                rawPath: `${currentPath.replace(/\/$/, '')}/${item.name}`
            }));

            formattedFiles.sort((a: FileItem, b: FileItem) => {
                if (a.type === 'folder' && b.type === 'file') return -1;
                if (a.type === 'file' && b.type === 'folder') return 1;
                return a.name.localeCompare(b.name);
            });

            setFiles(formattedFiles);
            setSelectedFiles([]);
        } catch (error) {
            console.error("Erro ao carregar arquivos:", error);
            setFiles([]);
            setHasError(true); // Define que deu erro
        } finally {
            const elapsed = Date.now() - start;
            const minTime = 100;

            if (elapsed < minTime) {
                await new Promise(res => setTimeout(res, minTime - elapsed));
            }

            loadingProgress.setLoadingBar(false);
            setIsLoading(false);
            setLoadingBar(false);
        }
    }, [currentPath, listFiles, setLoadingBar]);

    // -------------------------------------------------------------
    // ATUALIZAÇÃO AUTOMÁTICA AO VOLTAR PRA ABA / FOCAR NA JANELA
    // -------------------------------------------------------------
    useEffect(() => {
        const handleVisibilityChange = () => {
            if (document.visibilityState === 'visible' && server?.nodeUrl && !isEditOpen) {
                fetchFiles();
            }
        };

        const handleWindowFocus = () => {
            if (server?.nodeUrl && !isEditOpen) {
                fetchFiles();
            }
        };

        document.addEventListener("visibilitychange", handleVisibilityChange);
        window.addEventListener("focus", handleWindowFocus);

        return () => {
            document.removeEventListener("visibilitychange", handleVisibilityChange);
            window.removeEventListener("focus", handleWindowFocus);
        };
    }, [server?.nodeUrl, isEditOpen, fetchFiles]);


    useEffect(() => {
        if (server?.nodeUrl) {
            fetchFiles();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [currentPath, server?.nodeUrl]);


    const handleSelectAll = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.checked) setSelectedFiles(files.map((f) => f.name));
        else setSelectedFiles([]);
    };

    const handleSelect = (name: string) => {
        if (selectedFiles.includes(name)) {
            setSelectedFiles(selectedFiles.filter((n) => n !== name));
        } else {
            setSelectedFiles([...selectedFiles, name]);
        }
    };

    const handleFilesDrop = async (targetPath: string, droppedFiles: FileList) => {
        if (!droppedFiles || droppedFiles.length === 0) return;
        setLoadingBar(true);
        try {
            await Promise.all(
                Array.from(droppedFiles).map(file => uploadFile(targetPath, file))
            );
            fetchFiles();
        } catch (error) {
            console.error("Erro durante o upload via drop:", error);
        } finally {
            setLoadingBar(false);
        }
    };

    const handleFileSelected = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const selected = e.target.files;
        if (!selected || selected.length === 0) return;

        setLoadingBar(true);
        const targetPath = uploadTarget || currentPath;

        try {
            await Promise.all(
                Array.from(selected).map(file => uploadFile(targetPath, file))
            );
            fetchFiles();
        } catch (error) {
            console.error("Erro durante o upload:", error);
        } finally {
            setLoadingBar(false);
            setUploadTarget(null);
            if (fileInputRef.current) fileInputRef.current.value = "";
        }
    };

    const handleMassDelete = async () => {
        setLoadingBar(true);
        try {
            const fullPaths = selectedFiles.map(name => `${currentPath}/${name}`);
            await deleteItems(fullPaths);
            fetchFiles();
        } catch (error) {
            console.error("Erro ao deletar em massa:", error);
        } finally {
            setLoadingBar(false);
        }
    };

    const handleMassArchive = async () => {
        setLoadingBar(true);
        try {
            const fullPaths = selectedFiles.map(name => `${currentPath}/${name}`);
            await archiveItems(fullPaths);
            fetchFiles();
        } catch (error) {
            console.error("Erro ao compactar arquivos:", error);
        } finally {
            setLoadingBar(false);
        }
    };

    const safeAction = action || "";
    const actions = safeAction.split("/");
    const isEditing = actions[1] === 'edit' || isEditOpen;

    // Se estiver carregando OU se deu erro, ele exibe o LoadingPage
    if ((isLoading || hasError) && !isEditing) {
        return <AnimatePresence mode={"wait"}>
            <motion.div
                key="loading-terminal"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                transition={{
                    duration: 0.35,
                    ease: [0.4, 0, 0.2, 1], // ease suave tipo material
                }}
                className="absolute inset-0 flex flex-col items-center justify-center z-10"
            >
                <LoadingPage />
            </motion.div>
        </AnimatePresence> ;
    }

    const UploadProgressButton = () => {
        const radius = 14;
        const circumference = 2 * Math.PI * radius;
        const offset = circumference - ((totalUploadProgress || 0) / 100) * circumference;

        return (
            <button
                onClick={() => setIsUploadModalOpen(true)}
                className="relative flex items-center justify-center w-10 h-10 rounded-full hover:bg-white/5 transition"
                title="Ver Uploads"
            >
                <svg className="absolute inset-0 w-full h-full text-white/10" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r={radius} fill="none" stroke="currentColor" strokeWidth="3" />
                </svg>
                <svg className="absolute inset-0 w-full h-full text-[var(--color-info)] transition-all duration-300 ease-out" viewBox="0 0 36 36" style={{ transform: 'rotate(-90deg)', transformOrigin: '50% 50%' }}>
                    <circle cx="18" cy="18" r={radius} fill="none" stroke="currentColor" strokeWidth="3" strokeDasharray={circumference} strokeDashoffset={offset} strokeLinecap="round" />
                </svg>
                <CloudUpload className="w-4 h-4 text-(--color-text-label) relative z-10" />
            </button>
        );
    };

    return (
        <div className="flex-1 flex flex-col py-6 px-4 md:py-8 md:px-10 xl:px-20 overflow-x-hidden flex-1 flex w-full h-full text-(--color-text-value) relative z-0 overflow-hidden">
            <input
                type="file"
                ref={fileInputRef}
                className="hidden"
                multiple
                onChange={handleFileSelected}
            />

            <AnimatePresence mode="wait">
                {isEditing ? (
                    <motion.section
                        key="editor"
                        initial={{ opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: -10 }}
                        transition={{ duration: 0.2 }}
                        className="flex-1 flex flex-col h-full bg-[var(--color-secondary)] relative z-0"
                    >
                        <FileEditContainer />
                    </motion.section>
                ) : (
                    <motion.main
                        key={currentPath || "list"}
                        initial={{ opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: -10 }}
                        transition={{ duration: 0.2 }}
                        className={`flex-1 flex flex-col p-6 md:p-8 h-full overflow-hidden relative z-10 transition-colors ${dragOverPath === currentPath ? 'bg-white/5 ring-inset ring-2 ring-[var(--color-info)]' : ''}`}
                        onDragOver={(e) => { e.preventDefault(); e.stopPropagation(); setDragOverPath(currentPath); }}
                        onDragLeave={(e) => { e.preventDefault(); e.stopPropagation(); setDragOverPath(null); }}
                        onDrop={(e) => {
                            e.preventDefault(); e.stopPropagation();
                            setDragOverPath(null);
                            if(e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                                handleFilesDrop(currentPath, e.dataTransfer.files);
                            }
                        }}
                    >
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 shrink-0">
                            {/* pl-[12px] para igualar o recuo da lista que agora tem mais margin (p-2 + pl-1) */}
                            <div className="flex items-center gap-4 text-sm font-mono text-(--color-text-label) pl-[12px]">
                                <input
                                    type="checkbox"
                                    className={customCheckboxClass}
                                    onChange={handleSelectAll}
                                    checked={selectedFiles.length === files.length && files.length > 0}
                                />
                                <div className="flex items-center gap-1.5 flex-wrap">
                                    <span className="text-(--color-text-sub)/50 font-bold select-none">/</span>
                                    {breadcrumbs.map((crumb, index) => (
                                        <React.Fragment key={crumb.path}>
                                            <span
                                                className={`cursor-pointer transition ${crumb.isBase ? 'text-[var(--color-text-sub)] hover:text-white' : 'hover:text-white'}`}
                                                onClick={() => navigateToPath(crumb.path)}
                                            >
                                                {crumb.name}
                                            </span>
                                            {index < breadcrumbs.length - 1 && <span className="text-[var(--color-text-sub)]/50">/</span>}
                                        </React.Fragment>
                                    ))}
                                </div>
                            </div>

                            {/* BOTÕES DE AÇÃO */}
                            <div className="flex gap-2 items-center">
                                {uploadTasks.length > 0 && <UploadProgressButton />}
                                <Button variant="secondary" onClick={() => setIsCreateDirOpen(true)}>Criar Diretório</Button>
                                <Button variant="info" onClick={() => { setUploadTarget(null); fileInputRef.current?.click(); }}>Upload</Button>
                                <Button variant="info" onClick={() => setIsCreateFileOpen(true)}>Novo Arquivo</Button>
                            </div>
                        </div>

                        <div className="rounded-xl flex flex-col flex-1 min-h-0 overflow-hidden relative">

                            {dragOverPath === currentPath && files.length === 0 && (
                                <div className="absolute inset-0 z-10 border-2 border-dashed border-[var(--color-info)] bg-[var(--color-info)]/5 rounded-xl flex items-center justify-center pointer-events-none">
                                    <span className="text-[var(--color-info)] font-medium text-lg bg-[var(--color-terciary)] px-4 py-2 rounded-lg shadow-lg">Solte os arquivos para fazer Upload em {breadcrumbs[breadcrumbs.length - 1]?.name || './'}</span>
                                </div>
                            )}

                            <div className="flex-1 overflow-y-auto custom-scrollbar flex flex-col gap-[8px] p-[2px]">
                                {files.length === 0 && !isLoading && !hasError && (
                                    <div className="p-8 text-center text-[var(--color-text-sub)] m-auto">
                                        Este diretório está vazio. <br/><span className="text-xs opacity-60">Arraste arquivos aqui para fazer upload.</span>
                                    </div>
                                )}

                                {files.map((file) => (
                                    <div
                                        key={file.name}
                                        // Adicionado p-2 (antes era p-1) para dar mais espaço (margin/padding) no card do item
                                        className={`group flex items-center justify-between p-2 transition duration-150 rounded-md bg-(--color-secondary) hover:brightness-110
                                            ${dragOverPath === file.rawPath ? 'ring-2 ring-inset ring-(--color-info) bg-(--color-info)/20' : ''}
                                        `}
                                        onDragOver={(e) => {
                                            if (file.type === 'folder') {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                setDragOverPath(file.rawPath);
                                            }
                                        }}
                                        onDragLeave={(e) => {
                                            if (file.type === 'folder') {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                setDragOverPath(null);
                                            }
                                        }}
                                        onDrop={(e) => {
                                            if (file.type === 'folder') {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                setDragOverPath(null);
                                                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                                                    handleFilesDrop(file.rawPath, e.dataTransfer.files);
                                                }
                                            }
                                        }}
                                    >
                                        {/* Aumentado o gap para 4 e pl-1 para alinhar certinho com o header e afastar o texto */}
                                        <div className="flex items-center gap-4 flex-1 min-w-0 pl-1">
                                            <input
                                                type="checkbox"
                                                className={customCheckboxClass}
                                                checked={selectedFiles.includes(file.name)}
                                                onChange={() => handleSelect(file.name)}
                                            />
                                            {file.type === "folder" ? (
                                                <Folder className="w-5 h-5 text-[var(--color-text-sub)] fill-current flex-shrink-0" />
                                            ) : (
                                                <FileText className="w-5 h-5 text-[var(--color-text-sub)] flex-shrink-0" />
                                            )}
                                            <span
                                                className={`font-medium cursor-pointer transition truncate ${isEditable(file.name) ? 'text-[var(--color-text-label)] hover:text-[var(--color-info)]' : 'text-[var(--color-text-label)]'}`}
                                                onClick={() => {
                                                    if (file.type === "folder") {
                                                        navigateToPath(file.rawPath);
                                                    } else if (isEditable(file.name)) {
                                                        navigateToEdit(file.rawPath);
                                                    }
                                                }}
                                                title={file.name}
                                            >
                                                {file.name}
                                            </span>
                                        </div>

                                        <div className="hidden md:flex items-center gap-10 text-sm text-[var(--color-text-sub)] w-1/3 justify-end flex-shrink-0 pr-2">
                                            {file.type === 'file' && (
                                                <>
                                                    <span className="w-24 text-right">{file.size}</span>
                                                    <span className="w-40 text-right">{file.lastModified}</span>
                                                </>
                                            )}
                                        </div>

                                        <div className="pl-4 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity">
                                            <FileDropdown
                                                file={file}
                                                selectedFiles={selectedFiles}
                                                onRename={(name) => setRenameTarget(name)}
                                                onMove={(name) => setMoveTargets([name])}
                                                onSuccess={fetchFiles}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </motion.main>
                )}
            </AnimatePresence>

            {/* AÇÕES EM MASSA */}
            {selectedFiles.length > 0 && !isEditing && (
                <div className="fixed bottom-8 left-1/2 -translate-x-1/2 bg-[var(--color-terciary)] rounded-2xl shadow-[var(--card-shadow)] px-4 py-3 flex items-center gap-3 z-[80] border border-white/5 animate-in slide-in-from-bottom-5">
                    <Button variant="secondary" className="px-6" onClick={() => setMoveTargets(selectedFiles)}>Mover</Button>
                    <Button variant="info" className="px-6" onClick={handleMassArchive}>Compactar</Button>
                    <Button variant="danger" className="px-6" onClick={handleMassDelete}>Excluir</Button>
                </div>
            )}

            {/* MODAIS (Upload, Rename, etc...) */}
            {isUploadModalOpen && uploadTasks.length > 0 && (
                <div className="fixed inset-0 z-[11000] flex items-center justify-center bg-black/40 backdrop-blur-sm animate-in fade-in">
                    <div className="bg-[var(--color-secondary)] rounded-xl shadow-2xl w-full max-w-lg overflow-hidden border border-white/10">
                        <div className="p-5 flex justify-between items-center border-b border-white/5">
                            <h3 className="text-[var(--color-text-value)] font-medium text-lg flex items-center gap-2">
                                File Uploads
                                {uploadTasks.every(t => t.status === 'completed' || t.status === 'error') && (
                                    <span className="text-xs bg-[var(--color-info)]/20 text-[var(--color-info)] px-2 py-0.5 rounded-full font-bold">Concluído</span>
                                )}
                            </h3>
                            <button onClick={() => setIsUploadModalOpen(false)} className="bg-[var(--color-terciary)] hover:bg-white/10 transition p-1.5 rounded-md text-[var(--color-text-sub)] hover:text-white">
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        <div className="p-5">
                            <div className="space-y-2 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                                {uploadTasks.map((task, idx) => (
                                    <div key={idx} className="flex items-center justify-between bg-[var(--color-terciary)] p-3 rounded-lg border border-white/5">
                                        <div className="flex items-center gap-4 truncate">
                                            {task.status === 'uploading' ? (
                                                <svg className="animate-spin h-5 w-5 text-[var(--color-info)]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            ) : task.status === 'error' ? (
                                                <X className="w-5 h-5 text-red-400" />
                                            ) : (
                                                <Check className="w-5 h-5 text-green-400" />
                                            )}
                                            <span className="text-sm text-[var(--color-text-label)] font-mono truncate">{task.fileName}</span>
                                        </div>
                                        <span className="text-xs text-[var(--color-text-sub)] font-bold">{task.progress}%</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="p-4 border-t border-white/5 bg-[var(--color-terciary)] flex justify-end items-center gap-4">
                            <span className="flex-1 text-xs text-[var(--color-text-sub)] px-2 font-medium">Progresso Total: {totalUploadProgress}%</span>
                            <button onClick={() => { clearUploads(); setIsUploadModalOpen(false); }} className="text-sm text-[var(--color-text-value)] hover:text-red-400 transition font-medium">Limpar Uploads</button>
                            <Button variant="secondary" onClick={() => setIsUploadModalOpen(false)}>Fechar</Button>
                        </div>
                    </div>
                </div>
            )}

            <CreateDirModal isOpen={isCreateDirOpen} onClose={() => setIsCreateDirOpen(false)} onSuccess={fetchFiles} />
            <RenameModal target={renameTarget} onClose={() => setRenameTarget(null)} onSuccess={fetchFiles} />
            <MoveModal targets={moveTargets} onClose={() => setMoveTargets(null)} onSuccess={fetchFiles} />
            <CreateFileModal isOpen={isCreateFileOpen} onClose={() => setIsCreateFileOpen(false)} onSuccess={fetchFiles} />

        </div>
    );
}

export default function FileManagerContainer(props: FileManagerProps) {
    return (
        <FileManagerProvider>
            <FileManagerInner {...props} />
        </FileManagerProvider>
    );
}