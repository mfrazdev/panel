import React, { useState, useEffect } from "react";
import {
    Pencil, ArrowRightLeft, Lock, Archive, Trash2, Download, FileEdit, FileArchive
} from "lucide-react";
import {isEditable, useFileManager} from "./FileManagerContext";
import { AnimatePresence, motion } from "framer-motion";

interface FileItem {
    name: string;
    type: "folder" | "file";
    size: string;
    lastModified: string;
}

interface DropdownProps {
    file: FileItem;
    selectedFiles: string[];
    onRename: (name: string) => void;
    onMove: (name: string) => void;
    onSuccess?: () => void;
    fullPathOverride?: string;
}

const isArchive = (name: string) => /\.(zip|tar\.gz|tgz|rar)$/i.test(name);

// Ícone Minimalista
function MoreHorizontalIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>
        </svg>
    );
}

export default function FileDropdown({
                                         file,
                                         selectedFiles,
                                         onRename,
                                         onMove,
                                         onSuccess,
                                         fullPathOverride
                                     }: DropdownProps) {
    const [isOpen, setIsOpen] = useState(false);
    const {
        navigateToEdit,
        currentPath,
        deleteItems,
        archiveItems,
        unarchiveItem,
        downloadFile
    } = useFileManager();

    useEffect(() => {
        const handleClickOutside = () => setIsOpen(false);
        document.addEventListener("click", handleClickOutside);
        return () => document.removeEventListener("click", handleClickOutside);
    }, []);

    const toggleMenu = (e: React.MouseEvent) => {
        e.stopPropagation();
        setIsOpen(!isOpen);
    };

    const runAction = async (actionFn: () => Promise<void> | void) => {
        try {
            await actionFn();
            if (onSuccess) onSuccess();
        } catch (error) {
            console.error("Ação falhou:", error);
        } finally {
            setIsOpen(false);
        }
    };

    const fullPath = fullPathOverride ?? `${currentPath}/${file.name}`;
    const actionTarget = fullPathOverride ?? file.name;

    const handleEdit = () => {
        navigateToEdit(fullPath);
        setIsOpen(false);
    };

    const handleDelete = () => {
        if (!confirm(`Tem certeza que deseja excluir '${file.name}'?`)) return;
        runAction(() => deleteItems([fullPath]));
    };

    const handleArchive = () => {
        runAction(() => archiveItems([fullPath]));
    };

    const handleUnarchive = () => {
        runAction(() => unarchiveItem(fullPath));
    };

    const handleDownload = () => {
        runAction(() => downloadFile(fullPath));
    };

    return (
        <div className="relative">
            {/* Botão de Trigger */}
            <button
                onClick={toggleMenu}
                className="p-2 rounded-lg text-[var(--color-text-sub)] hover:text-[var(--color-text-value)] hover:bg-white/5 transition-colors cursor-pointer"
            >
                <MoreHorizontalIcon />
            </button>

            {/* Menu Animado */}
            <AnimatePresence>
                {isOpen && (
                    <motion.div
                        initial={{ opacity: 0, y: -5, scale: 0.95 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: -5, scale: 0.95 }}
                        transition={{ duration: 0.15, ease: "easeOut" }}
                        className="absolute right-0 top-full mt-2 w-48 bg-[var(--color-terciary)] rounded-xl shadow-2xl z-[100] text-[var(--color-text-value)] py-1.5 font-medium text-[13px] border border-white/5"
                    >
                        {isEditable(file.name) && (
                            <button
                                onClick={handleEdit}
                                className="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center gap-3 transition-colors"
                            >
                                <FileEdit className="w-4 h-4 text-[var(--color-text-sub)]" /> Editar
                            </button>
                        )}

                        <button
                            onClick={() => { onRename(actionTarget); setIsOpen(false); }}
                            className="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center gap-3 transition-colors"
                        >
                            <Pencil className="w-4 h-4 text-[var(--color-text-sub)]" /> Renomear
                        </button>

                        <button
                            onClick={() => { onMove(actionTarget); setIsOpen(false); }}
                            className="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center gap-3 transition-colors"
                        >
                            <ArrowRightLeft className="w-4 h-4 text-[var(--color-text-sub)]" /> Mover
                        </button>

                        {isArchive(file.name) ? (
                            <button
                                onClick={handleUnarchive}
                                className="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center gap-3 transition-colors"
                            >
                                <FileArchive className="w-4 h-4 text-[var(--color-text-sub)]" /> Extrair
                            </button>
                        ) : (
                            <button
                                onClick={handleArchive}
                                className="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center gap-3 transition-colors"
                            >
                                <Archive className="w-4 h-4 text-[var(--color-text-sub)]" /> Compactar
                            </button>
                        )}

                        <button
                            onClick={() => { alert("Configuração de permissões em breve"); setIsOpen(false); }}
                            className="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center gap-3 transition-colors"
                        >
                            <Lock className="w-4 h-4 text-[var(--color-text-sub)]" /> Permissões
                        </button>

                        {file.type !== "folder" && (
                            <button
                                onClick={handleDownload}
                                className="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center gap-3 transition-colors"
                            >
                                <Download className="w-4 h-4 text-[var(--color-text-sub)]" /> Baixar
                            </button>
                        )}

                        {/* Divisória */}
                        <div className="h-[1px] bg-white/5 my-1.5 mx-2"></div>

                        <button
                            onClick={handleDelete}
                            className="w-full text-left px-4 py-2.5 hover:bg-[var(--color-danger)]/10 text-[var(--color-danger)] flex items-center gap-3 transition-colors"
                        >
                            <Trash2 className="w-4 h-4" /> Excluir
                        </button>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}