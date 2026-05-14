import React, { useState, useRef, useEffect, useCallback } from "react";
import Button from "@/web/components/commons/components/Button";

import {
    Folder, FileText,
    X, Check, CloudUpload,
    Archive, Trash2, ArrowRightLeft, FileUp, FilePlus, FolderPlus
} from "lucide-react";

import FileEditContainer from "@/web/components/server/filemanager/FileEdit";
import CreateDirModal from "./modals/CreateDirModal";
import RenameModal from "./modals/RenameModal";
import MoveModal from "./modals/MoveModal";
import CreateFileModal from "./modals/CreateFileModal";
import { useServerContext } from "@/web/contexts/ServerContext";
import {FileManagerProvider, isEditable, useFileManager} from "./FileManagerContext";
import FileDropdown from "./Dropdown";
import { useLoading } from "@/web/components/wrappers/Wrapper";
import LoadingPage from "@/web/components/commons/LoadingPage";
import { AnimatePresence, motion } from "framer-motion";

// === TIPAGENS E HELPERS ===
type FileItem = {
    name: string;
    type: "folder" | "file";
    size: string;
    lastModified: string;
    rawPath: string;
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

// Checkbox modernizado
const customCheckboxClass = "appearance-none w-4.5 h-4.5 rounded-[4px] border border-white/10 bg-[var(--color-terciary)] hover:bg-white/5 checked:bg-[var(--color-primary)] checked:border-[var(--color-primary)] cursor-pointer flex-shrink-0 relative transition-all shadow-sm before:content-[''] checked:before:block before:hidden before:absolute before:left-[5px] before:top-[2px] before:w-[5px] before:h-[9px] before:border-solid before:border-[#09090b] before:border-r-[2px] before:border-b-[2px] before:rotate-45";

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
    const [hasError, setHasError] = useState(false);

    const [isCreateDirOpen, setIsCreateDirOpen] = useState(false);
    const [isCreateFileOpen, setIsCreateFileOpen] = useState(false);
    const [renameTarget, setRenameTarget] = useState<string | null>(null);
    const [moveTargets, setMoveTargets] = useState<string[] | null>(null);
    const [isUploadModalOpen, setIsUploadModalOpen] = useState(false);

    const [uploadTarget, setUploadTarget] = useState<string | null>(null);
    const [dragOverPath, setDragOverPath] = useState<string | null>(null);

    const fileInputRef = useRef<HTMLInputElement>(null);
    const uploadTasks = Object.values(uploadState);
    const breadcrumbs = getBreadcrumbs(currentPath);

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

    const fetchFiles = useCallback(async () => {
        const start = Date.now();
        setLoadingBar(true);
        setIsLoading(true);
        setHasError(false);

        try {
            const data = await listFiles(currentPath);
            const formattedFiles = (data.items || []).map((item: any) => ({
                name: item.name,
                type: item.type,
                size: formatBytes(item.size),
                lastModified: formatDate(item.lastModified),
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
            setHasError(true);
        } finally {
            const elapsed = Date.now() - start;
            if (elapsed < 100) await new Promise(res => setTimeout(res, 100 - elapsed));
            setIsLoading(false);
            setLoadingBar(false);
        }
    }, [currentPath, listFiles, setLoadingBar]);

    // Background refresh setup
    useEffect(() => {
        let hiddenAt: number | null = null;
        const handleVisibilityChange = () => {
            if (document.visibilityState === 'hidden') {
                hiddenAt = Date.now();
                return;
            }
            if (document.visibilityState === 'visible' && server?.nodeUrl && !isEditOpen) {
                if (hiddenAt && Date.now() - hiddenAt >= 10000) fetchFiles();
            }
        };
        const handleWindowBlur = () => { hiddenAt = Date.now(); };
        const handleWindowFocus = () => {
            if (server?.nodeUrl && !isEditOpen && hiddenAt && Date.now() - hiddenAt >= 10000) fetchFiles();
        };

        document.addEventListener("visibilitychange", handleVisibilityChange);
        window.addEventListener("blur", handleWindowBlur);
        window.addEventListener("focus", handleWindowFocus);

        return () => {
            document.removeEventListener("visibilitychange", handleVisibilityChange);
            window.removeEventListener("blur", handleWindowBlur);
            window.removeEventListener("focus", handleWindowFocus);
        };
    }, [server?.nodeUrl, isEditOpen, fetchFiles]);

    useEffect(() => {
        if (server?.nodeUrl) fetchFiles();
    }, [currentPath, server?.nodeUrl]);


    // === ACTIONS ===
    const handleSelectAll = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedFiles(e.target.checked ? files.map((f) => f.name) : []);
    };

    const handleSelect = (name: string) => {
        setSelectedFiles(prev => prev.includes(name) ? prev.filter(n => n !== name) : [...prev, name]);
    };

    const handleFilesDrop = async (targetPath: string, droppedFiles: FileList) => {
        if (!droppedFiles || droppedFiles.length === 0) return;
        setLoadingBar(true);
        try {
            await Promise.all(Array.from(droppedFiles).map(file => uploadFile(targetPath, file)));
            fetchFiles();
        } catch (error) {
            console.error("Erro no drop:", error);
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
            await Promise.all(Array.from(selected).map(file => uploadFile(targetPath, file)));
            fetchFiles();
        } catch (error) {
            console.error("Erro no upload:", error);
        } finally {
            setLoadingBar(false);
            setUploadTarget(null);
            if (fileInputRef.current) fileInputRef.current.value = "";
        }
    };

    const handleMassDelete = async () => {
        setLoadingBar(true);
        try {
            await deleteItems(selectedFiles.map(name => `${currentPath}/${name}`));
            fetchFiles();
        } finally {
            setLoadingBar(false);
        }
    };

    const handleMassArchive = async () => {
        setLoadingBar(true);
        try {
            await archiveItems(selectedFiles.map(name => `${currentPath}/${name}`));
            fetchFiles();
        } finally {
            setLoadingBar(false);
        }
    };

    const isEditing = action.split("/")[1] === 'edit' || isEditOpen;

    if ((isLoading || hasError) && !isEditing) {
        return (
            <AnimatePresence mode="wait">
                <motion.div key="loading-fm" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="absolute inset-0 flex flex-col items-center justify-center z-10">
                    <LoadingPage />
                </motion.div>
            </AnimatePresence>
        );
    }

    const UploadProgressButton = () => {
        const radius = 14;
        const circumference = 2 * Math.PI * radius;
        const offset = circumference - ((totalUploadProgress || 0) / 100) * circumference;

        return (
            <button onClick={() => setIsUploadModalOpen(true)} className="relative flex items-center justify-center w-10 h-10 rounded-xl bg-white/5 border border-white/5 hover:bg-white/10 transition-colors cursor-pointer" title="Ver Uploads">
                <svg className="absolute inset-0 w-full h-full text-white/10" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r={radius} fill="none" stroke="currentColor" strokeWidth="3" />
                </svg>
                <svg className="absolute inset-0 w-full h-full text-[var(--color-primary)] transition-all duration-300 ease-out" viewBox="0 0 36 36" style={{ transform: 'rotate(-90deg)', transformOrigin: '50% 50%' }}>
                    <circle cx="18" cy="18" r={radius} fill="none" stroke="currentColor" strokeWidth="3" strokeDasharray={circumference} strokeDashoffset={offset} strokeLinecap="round" />
                </svg>
                <CloudUpload className="w-4 h-4 text-[var(--color-text-value)] relative z-10" />
            </button>
        );
    };

    return (
        <div className="flex-1 flex flex-col py-6 px-4 md:py-8 md:px-10 xl:px-20 overflow-x-hidden flex-1 flex w-full h-full text-[var(--color-text-value)] relative z-0 overflow-hidden animate-[fadeIn_0.4s_ease-out]">
            <input type="file" ref={fileInputRef} className="hidden" multiple onChange={handleFileSelected} />

            <AnimatePresence mode="wait">
                {isEditing ? (
                    <motion.section key="editor" initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -10 }} transition={{ duration: 0.2 }} className="flex-1 flex flex-col h-full relative z-0">
                        <FileEditContainer />
                    </motion.section>
                ) : (
                    <motion.main
                        key="list"
                        initial={{ opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: -10 }}
                        transition={{ duration: 0.2 }}
                        className="flex-1 flex flex-col h-full relative z-10 gap-6"
                        onDragOver={(e) => { e.preventDefault(); e.stopPropagation(); setDragOverPath(currentPath); }}
                        onDragLeave={(e) => { e.preventDefault(); e.stopPropagation(); setDragOverPath(null); }}
                        onDrop={(e) => {
                            e.preventDefault(); e.stopPropagation();
                            setDragOverPath(null);
                            if (e.dataTransfer.files?.length > 0) handleFilesDrop(currentPath, e.dataTransfer.files);
                        }}
                    >
                        {/* HEADER REDESENHADO */}
                        <div className="flex flex-col xl:flex-row xl:items-end justify-between gap-6 shrink-0">
                            <div>
                                {/* Breadcrumbs integrados no título */}
                                <div className="flex items-center gap-1.5 flex-wrap text-sm font-mono text-[var(--color-text-sub)] bg-[var(--color-secondary)] border border-white/5 px-3 py-1.5 rounded-lg w-fit shadow-sm">
                                    <span className="opacity-50 font-bold select-none">/</span>
                                    {breadcrumbs.map((crumb, index) => (
                                        <React.Fragment key={crumb.path}>
                                            <span className={`cursor-pointer transition hover:text-[var(--color-text-value)] ${crumb.isBase ? 'text-[var(--color-text-sub)]' : 'text-[var(--color-primary)] font-bold'}`} onClick={() => navigateToPath(crumb.path)}>
                                                {crumb.name}
                                            </span>
                                            {index < breadcrumbs.length - 1 && <span className="opacity-50">/</span>}
                                        </React.Fragment>
                                    ))}
                                </div>
                            </div>

                            {/* BOTÕES DE AÇÃO */}
                            <div className="flex flex-wrap gap-3 items-center">
                                {uploadTasks.length > 0 && <UploadProgressButton />}
                                <Button variant="secondary" className="!py-2.5 !px-4 !text-[13px] flex items-center gap-2" onClick={() => setIsCreateDirOpen(true)}>
                                    <FolderPlus className="w-4 h-4" /> Criar Pasta
                                </Button>
                                <Button variant="info" className="!py-2.5 !px-4 !text-[13px] flex items-center gap-2" onClick={() => setIsCreateFileOpen(true)}>
                                    <FilePlus className="w-4 h-4" /> Novo Arquivo
                                </Button>
                                <Button variant="primary" className="!py-2.5 !px-5 !text-[13px] flex items-center gap-2" onClick={() => { setUploadTarget(null); fileInputRef.current?.click(); }}>
                                    <FileUp className="w-4 h-4" /> Upload
                                </Button>
                            </div>
                        </div>

                        {/* CONTAINER DA LISTA */}
                        <div className={`flex-1 flex flex-col bg-[var(--color-secondary)] border border-white/5 rounded-2xl shadow-sm overflow-hidden relative transition-colors duration-300 ${dragOverPath === currentPath ? 'ring-2 ring-inset ring-[var(--color-primary)] bg-white/[0.02]' : ''}`}>

                            {/* OVERLAY DRAG AND DROP */}
                            {dragOverPath === currentPath && (
                                <div className="absolute inset-0 z-20 border-2 border-dashed border-[var(--color-primary)] bg-[var(--color-primary)]/5 flex items-center justify-center pointer-events-none rounded-2xl">
                                    <span className="text-[var(--color-primary)] font-bold text-lg bg-[var(--color-terciary)] border border-[var(--color-primary)]/20 px-6 py-3 rounded-xl shadow-2xl flex items-center gap-3">
                                        <CloudUpload className="w-6 h-6 animate-bounce" /> Solte os arquivos aqui
                                    </span>
                                </div>
                            )}

                            {/* TABLE HEADER */}
                            <div className="hidden md:flex items-center px-5 py-4 border-b border-white/5 bg-white/[0.01] text-[12px] font-bold text-[var(--color-text-sub)] uppercase tracking-wider">
                                <div className="flex items-center gap-4 flex-1 pl-1">
                                    <input type="checkbox" className={customCheckboxClass} onChange={handleSelectAll} checked={selectedFiles.length === files.length && files.length > 0} />
                                    <span>Nome do Arquivo</span>
                                </div>
                                <div className="flex items-center gap-10 w-1/3 justify-end pr-14">
                                    <span className="w-24 text-right">Tamanho</span>
                                    <span className="w-40 text-right">Modificação</span>
                                </div>
                            </div>

                            {/* TABLE BODY */}
                            <div className="flex-1 overflow-y-auto custom-scrollbar flex flex-col">
                                {files.length === 0 && !isLoading && !hasError && (
                                    /* EMPTY STATE */
                                    <div className="m-auto flex flex-col items-center justify-center text-center p-10">
                                        <div className="w-16 h-16 rounded-full bg-[var(--color-terciary)] border border-white/5 flex items-center justify-center text-[var(--color-text-sub)] mb-4 shadow-sm">
                                            <Folder className="w-7 h-7" />
                                        </div>
                                        <p className="text-[15px] font-bold text-[var(--color-text-value)] tracking-tight">Pasta Vazia</p>
                                        <p className="text-[13px] font-medium text-[var(--color-text-sub)] mt-1 max-w-xs">
                                            Arraste arquivos para cá ou use os botões acima para criar conteúdo.
                                        </p>
                                    </div>
                                )}

                                {files.map((file) => (
                                    <div
                                        key={file.name}
                                        className={`group flex items-center justify-between px-5 py-3 border-b border-white/[0.02] transition-colors duration-200 hover:bg-white/[0.02] last:border-none
                                            ${dragOverPath === file.rawPath ? 'bg-[var(--color-primary)]/10 ring-1 ring-inset ring-[var(--color-primary)]' : ''}
                                        `}
                                        onDragOver={(e) => {
                                            if (file.type === 'folder') { e.preventDefault(); e.stopPropagation(); setDragOverPath(file.rawPath); }
                                        }}
                                        onDragLeave={(e) => {
                                            if (file.type === 'folder') { e.preventDefault(); e.stopPropagation(); setDragOverPath(null); }
                                        }}
                                        onDrop={(e) => {
                                            if (file.type === 'folder') {
                                                e.preventDefault(); e.stopPropagation(); setDragOverPath(null);
                                                if (e.dataTransfer.files?.length > 0) handleFilesDrop(file.rawPath, e.dataTransfer.files);
                                            }
                                        }}
                                    >
                                        <div className="flex items-center gap-4 flex-1 min-w-0 pl-1">
                                            <input type="checkbox" className={customCheckboxClass} checked={selectedFiles.includes(file.name)} onChange={() => handleSelect(file.name)} />
                                            {file.type === "folder" ? (
                                                <Folder className="w-5 h-5 text-[var(--color-primary)] fill-[var(--color-primary)]/20 flex-shrink-0" />
                                            ) : (
                                                <FileText className="w-5 h-5 text-[var(--color-text-sub)] flex-shrink-0" />
                                            )}
                                            <span
                                                className={`font-medium cursor-pointer transition-colors truncate text-[14px] ${isEditable(file.name) || file.type === "folder" ? 'text-[var(--color-text-value)] hover:text-[var(--color-primary)]' : 'text-[var(--color-text-label)]'}`}
                                                onClick={() => {
                                                    if (file.type === "folder") navigateToPath(file.rawPath);
                                                    else if (isEditable(file.name)) navigateToEdit(file.rawPath);
                                                }}
                                                title={file.name}
                                            >
                                                {file.name}
                                            </span>
                                        </div>

                                        <div className="hidden md:flex items-center gap-10 text-[13px] font-medium text-[var(--color-text-sub)] w-1/3 justify-end flex-shrink-0 pr-4">
                                            {file.type === 'file' ? (
                                                <>
                                                    <span className="w-24 text-right">{file.size}</span>
                                                    <span className="w-40 text-right">{file.lastModified}</span>
                                                </>
                                            ) : (
                                                <>
                                                    <span className="w-24 text-right">--</span>
                                                    <span className="w-40 text-right">{file.lastModified}</span>
                                                </>
                                            )}
                                        </div>

                                        <div className="pl-2">
                                            <FileDropdown file={file} selectedFiles={selectedFiles} onRename={(name) => setRenameTarget(name)} onMove={(name) => setMoveTargets([name])} onSuccess={fetchFiles} />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </motion.main>
                )}
            </AnimatePresence>

            {/* AÇÕES EM MASSA (Floating Bar Animada) */}
            <AnimatePresence>
                {selectedFiles.length > 0 && !isEditing && (
                    <motion.div
                        initial={{ y: 50, opacity: 0, x: '-50%' }}
                        animate={{ y: 0, opacity: 1, x: '-50%' }}
                        exit={{ y: 50, opacity: 0, x: '-50%' }}
                        className="fixed bottom-8 left-1/2 bg-[var(--color-terciary)]/90 backdrop-blur-md rounded-2xl shadow-2xl px-5 py-3 flex items-center gap-4 z-[80] border border-white/10"
                    >
                        <span className="text-[13px] font-bold text-[var(--color-text-value)] mr-2 bg-white/5 px-3 py-1.5 rounded-lg border border-white/5">
                            {selectedFiles.length} selecionados
                        </span>
                        <Button variant="secondary" className="!py-2 !px-4 !text-[13px] flex items-center gap-2" onClick={() => setMoveTargets(selectedFiles)}>
                            <ArrowRightLeft className="w-4 h-4" /> Mover
                        </Button>
                        <Button variant="info" className="!py-2 !px-4 !text-[13px] flex items-center gap-2" onClick={handleMassArchive}>
                            <Archive className="w-4 h-4" /> Compactar
                        </Button>
                        <div className="w-[1px] h-6 bg-white/10 mx-1" />
                        <Button variant="danger" className="!py-2 !px-4 !text-[13px] flex items-center gap-2" onClick={handleMassDelete}>
                            <Trash2 className="w-4 h-4" /> Excluir
                        </Button>
                    </motion.div>
                )}
            </AnimatePresence>

            {/* MODAL DE UPLOADS (Design Clean) */}
            <AnimatePresence>
                {isUploadModalOpen && uploadTasks.length > 0 && (
                    <motion.div
                        initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}
                        className="fixed inset-0 z-[11000] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
                    >
                        <motion.div
                            initial={{ scale: 0.95, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} exit={{ scale: 0.95, opacity: 0 }}
                            className="bg-[var(--color-secondary)] rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-white/5 flex flex-col max-h-[85vh]"
                        >
                            <div className="p-6 flex justify-between items-center border-b border-white/5 bg-white/[0.01]">
                                <h3 className="text-[var(--color-text-value)] font-black text-xl flex items-center gap-3 tracking-tight">
                                    <CloudUpload className="w-6 h-6 text-[var(--color-primary)]" /> Status dos Uploads
                                    {uploadTasks.every(t => t.status === 'completed' || t.status === 'error') && (
                                        <span className="text-[10px] uppercase tracking-wider bg-[var(--color-success)]/10 text-[var(--color-success)] px-2.5 py-1 rounded-md font-bold ml-2">Concluído</span>
                                    )}
                                </h3>
                                <button onClick={() => setIsUploadModalOpen(false)} className="text-[var(--color-text-sub)] hover:text-white transition-colors p-1">
                                    <X className="w-6 h-6" />
                                </button>
                            </div>

                            <div className="p-6 flex-1 overflow-y-auto custom-scrollbar bg-[var(--color-secondary)]">
                                <div className="space-y-3">
                                    {uploadTasks.map((task, idx) => (
                                        <div key={idx} className="flex items-center justify-between bg-[var(--color-terciary)] p-3.5 rounded-xl border border-white/5 shadow-sm">
                                            <div className="flex items-center gap-3.5 min-w-0 pr-4">
                                                {task.status === 'uploading' ? (
                                                    <svg className="animate-spin h-5 w-5 text-[var(--color-primary)] shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                ) : task.status === 'error' ? (
                                                    <X className="w-5 h-5 text-[var(--color-danger)] shrink-0" />
                                                ) : (
                                                    <Check className="w-5 h-5 text-[var(--color-success)] shrink-0" />
                                                )}
                                                <span className="text-[14px] text-[var(--color-text-value)] font-medium truncate">{task.fileName}</span>
                                            </div>
                                            <span className="text-[12px] text-[var(--color-text-sub)] font-bold bg-white/5 px-2 py-1 rounded-md shrink-0 border border-white/5">{task.progress}%</span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="p-6 border-t border-white/5 bg-white/[0.01] flex justify-between items-center gap-4">
                                <span className="text-[12px] font-bold text-[var(--color-text-sub)] uppercase tracking-wider">
                                    Progresso: <span className="text-[var(--color-primary)]">{totalUploadProgress}%</span>
                                </span>
                                <div className="flex gap-3">
                                    <Button variant="ghost" onClick={() => { clearUploads(); setIsUploadModalOpen(false); }} className="!py-2 !px-4 !text-[13px] text-[var(--color-danger)] hover:bg-[var(--color-danger)]/10">
                                        Limpar
                                    </Button>
                                    <Button variant="secondary" onClick={() => setIsUploadModalOpen(false)} className="!py-2 !px-4 !text-[13px]">
                                        Fechar
                                    </Button>
                                </div>
                            </div>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>

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