import React, { useState, useEffect, useRef } from "react";
import Editor, { useMonaco } from "@monaco-editor/react";
import Button from "@/web/components/commons/components/Button";
import { ArrowLeft } from "lucide-react";
import { useFileManager } from "./FileManagerContext";
import { useServerContext } from "@/web/contexts/ServerContext";
import { useToast } from "@/web/contexts/ToastContext";
import LoadingPage from "@/web/components/commons/LoadingPage"; // Importando o LoadingPage
import { AnimatePresence, motion } from "framer-motion"; // Importando framer-motion para transição suave

const SUPPORTED_LANGUAGES = [
    { value: "json", label: "JSON" },
    { value: "yaml", label: "YAML / YML" },
    { value: "properties", label: "Properties" },
    { value: "xml", label: "XML" },
    { value: 'php', label: 'PhP'},
    { value: "javascript", label: "JavaScript" },
    { value: "typescript", label: "TypeScript" },
    { value: "shell", label: "Shell Script (.sh)" },
    { value: "plaintext", label: "Plain Text" },
];

export default function FileEditContainer() {
    const {
        editingFilePath,
        getBreadcrumbs,
        navigateToPath,
        closeEdit,
        readFile,
        writeFile,
        listFiles
    } = useFileManager();

    const [content, setContent] = useState("");
    const [language, setLanguage] = useState("plaintext");
    const [isSaving, setIsSaving] = useState(false);
    const [isLoading, setIsLoading] = useState(true);

    const toast = useToast()
    const monaco = useMonaco();
    const server = useServerContext();

    // Guarda as tipagens e modelos do arquivo atual para matar elas ao trocar de arquivo
    const loadedLibsRef = useRef<any[]>([]);

    const safeFilePath = editingFilePath || "";
    const breadcrumbs = getBreadcrumbs(safeFilePath);

    // Define a linguagem com base na extensão
    useEffect(() => {
        if (!safeFilePath) return;

        if (safeFilePath.endsWith(".json")) setLanguage("json");
        else if (safeFilePath.endsWith(".yml") || safeFilePath.endsWith(".yaml")) setLanguage("yaml");
        else if (safeFilePath.endsWith(".properties")) setLanguage("properties");
        else if (safeFilePath.endsWith(".sh")) setLanguage("shell");
        else if (safeFilePath.endsWith(".xml")) setLanguage("xml");
        else if (safeFilePath.match(/\.(js|jsx)$/)) setLanguage("javascript");
        else if (safeFilePath.match(/\.(ts|tsx)$/)) setLanguage("typescript");
        else setLanguage("plaintext");
    }, [safeFilePath]);

    // Define o tema do Monaco
    useEffect(() => {
        if (monaco) {
            monaco.editor.defineTheme('pterodactyl-dark', {
                base: 'vs-dark',
                inherit: true,
                rules: [],
                colors: {
                    'editor.background': '#0f1419',
                    'editor.lineHighlightBackground': '#151b21',
                }
            });
            monaco.editor.setTheme('pterodactyl-dark');
        }
    }, [monaco]);

    // Busca o conteúdo do arquivo
    useEffect(() => {
        if (!safeFilePath || !server?.nodeUrl) return;

        const fetchFileContent = async () => {
            setIsLoading(true);
            try {
                const response = await readFile(safeFilePath);
                setContent(response.content || "");
            } catch (error) {
                console.error("Ocorreu um erro ao carregar o contéudo:", error);
                setContent("// Ocorreu um erro ao carregar o contéudo.");
            } finally {
                setIsLoading(false);
            }
        };

        fetchFileContent();
        // IGNORANDO O LINT E REMOVENDO O readFile PARA MATAR O LOOP INFINITO:
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [safeFilePath, server?.nodeUrl]);

    // Setup do Monaco (Compilador configurado para Intellisense JS/TS)
    const handleEditorDidMount = (editor: any, monacoInstance: any) => {
        const compilerOptions = {
            target: monacoInstance.languages.typescript.ScriptTarget.ESNext,
            allowNonTsExtensions: true,
            moduleResolution: monacoInstance.languages.typescript.ModuleResolutionKind.NodeJs,
            module: monacoInstance.languages.typescript.ModuleKind.CommonJS,
            noEmit: true,
            esModuleInterop: true,
            allowSyntheticDefaultImports: true,
            allowJs: true, // Necessário para Intellisense pegar arquivos .js
            checkJs: true, // Mostra errinhos em JS também
            fixedOverflowWidgets: true,
            baseUrl: "file:///",
            paths: {
                "*": ["node_modules/*", "node_modules/@types/*"]
            }
        };

        monacoInstance.languages.typescript.javascriptDefaults.setCompilerOptions(compilerOptions);
        monacoInstance.languages.typescript.typescriptDefaults.setCompilerOptions(compilerOptions);

        monacoInstance.languages.typescript.javascriptDefaults.setDiagnosticsOptions({
            noSemanticValidation: false,
            noSyntaxValidation: false,
        });

        monacoInstance.languages.typescript.typescriptDefaults.setDiagnosticsOptions({
            noSemanticValidation: false,
            noSyntaxValidation: false,
        });

        const globalsLib = `
            declare var require: any;
            declare var module: any;
            declare var exports: any;
            declare var __dirname: string;
            declare var __filename: string;
            declare var process: any;
            declare var console: any;
        `;
        monacoInstance.languages.typescript.javascriptDefaults.addExtraLib(globalsLib, 'file:///node_globals.d.ts');
        monacoInstance.languages.typescript.typescriptDefaults.addExtraLib(globalsLib, 'file:///node_globals.d.ts');
    };

    // Lógica de dependências (Models locais e Libs NPM)
    useEffect(() => {
        if (!monaco || !safeFilePath) return;

        const monacoAny = monaco as any;
        if (!safeFilePath.match(/\.(js|ts|jsx|tsx)$/)) return;

        // 1. LIMPEZA: Destrói as definições de tipagem e modelos do arquivo anterior
        loadedLibsRef.current.forEach(lib => {
            if (lib && typeof lib.dispose === 'function') lib.dispose();
        });
        loadedLibsRef.current = [];

        const loadDependencies = async () => {
            const dirPath = safeFilePath.substring(0, safeFilePath.lastIndexOf('/'));
            if (!dirPath) return;

            // Para pacotes NPM baixados da internet (Tipagens)
            const addLib = (content: string, uri: string) => {
                try {
                    const jsLib = monacoAny.languages.typescript.javascriptDefaults.addExtraLib(content, uri);
                    const tsLib = monacoAny.languages.typescript.typescriptDefaults.addExtraLib(content, uri);
                    loadedLibsRef.current.push(jsLib, tsLib);
                } catch (e) { }
            };

            // Para arquivos da mesma pasta (Permite que imports relativos funcionem)
            const addLocalModel = (content: string, filePath: string) => {
                try {
                    const uri = monacoAny.Uri.file(filePath);
                    let model = monacoAny.editor.getModel(uri);
                    if (!model) {
                        model = monacoAny.editor.createModel(content, undefined, uri);
                        loadedLibsRef.current.push(model); // Guarda para limpar quando trocar de tela
                    } else {
                        model.setValue(content);
                    }
                } catch (e) { }
            };

            // 1. Carrega os arquivos da mesma pasta e registra como Models
            try {
                const response = await listFiles(dirPath);
                if (response?.items) {
                    for (const item of response.items) {
                        if (item.type === 'file' &&
                            item.name !== safeFilePath.split('/').pop() &&
                            /\.(js|ts|jsx|tsx|d\.ts|json)$/.test(item.name)) {

                            const itemPath = `${dirPath}/${item.name}`;
                            try {
                                const fileData = await readFile(itemPath);
                                if (fileData?.content) {
                                    // Se for arquivo de tipagem pura, adiciona como Lib, senão cria o Model local
                                    if (item.name.endsWith('.d.ts')) {
                                        addLib(fileData.content, `file://${itemPath}`);
                                    } else {
                                        addLocalModel(fileData.content, itemPath);
                                    }
                                }
                            } catch (e) {}
                        }
                    }
                }
            } catch (err) {
                console.warn(err);
            }

            // 2. Busca tipagens de pacotes do npmjs
            const fetchNpmType = async (pkgName: string) => {
                try {
                    const res = await fetch(`https://cdn.jsdelivr.net/npm/@types/${pkgName}/index.d.ts`);
                    if (res.ok) {
                        const content = await res.text();
                        addLib(content, `file:///node_modules/@types/${pkgName}/index.d.ts`);
                        return;
                    }
                    const res2 = await fetch(`https://cdn.jsdelivr.net/npm/${pkgName}/index.d.ts`);
                    if (res2.ok) {
                        const content = await res2.text();
                        addLib(content, `file:///node_modules/${pkgName}/index.d.ts`);
                    }
                } catch (e) { }
            };

            // 3. Procura package.json e tsconfig.json subindo nas pastas
            try {
                let currentDir = dirPath;
                let pkgData = null;
                let tsConfigData = null;

                for (let i = 0; i < 4; i++) {
                    if (!pkgData) {
                        try {
                            const res = await readFile(`${currentDir}/package.json`);
                            if (res?.content) pkgData = res;
                        } catch (e) {}
                    }
                    if (!tsConfigData) {
                        try {
                            const res = await readFile(`${currentDir}/tsconfig.json`);
                            if (res?.content) tsConfigData = res;
                        } catch (e) {}
                    }

                    if (pkgData && tsConfigData) break;

                    const lastSlash = currentDir.lastIndexOf('/');
                    if (lastSlash === -1) break;
                    currentDir = currentDir.substring(0, lastSlash);
                }

                if (tsConfigData?.content) {
                    try {
                        const cleanJson = tsConfigData.content
                            .replace(/\/\*[\s\S]*?\*\/|\/\/.*$/gm, '')
                            .replace(/,\s*([\]}])/g, '$1');

                        const tsConfig = JSON.parse(cleanJson);
                        if (tsConfig.compilerOptions) {
                            const mergedOptions = {
                                target: monacoAny.languages.typescript.ScriptTarget.ESNext,
                                moduleResolution: monacoAny.languages.typescript.ModuleResolutionKind.NodeJs,
                                module: monacoAny.languages.typescript.ModuleKind.CommonJS,
                                ...tsConfig.compilerOptions,
                                allowNonTsExtensions: true,
                            };
                            monacoAny.languages.typescript.javascriptDefaults.setCompilerOptions(mergedOptions);
                            monacoAny.languages.typescript.typescriptDefaults.setCompilerOptions(mergedOptions);
                        }
                    } catch (e) {}
                }

                if (pkgData?.content) {
                    const pkg = JSON.parse(pkgData.content);
                    const deps = { ...pkg.dependencies, ...pkg.devDependencies };

                    const ignorePackages = ['typescript', 'ts-node', 'nodemon'];
                    const depNames = Object.keys(deps).filter(d => !d.startsWith('@types/') && !ignorePackages.includes(d));

                    depNames.forEach(fetchNpmType);

                    if (depNames.includes('express')) {
                        fetchNpmType('express-serve-static-core');
                        fetchNpmType('serve-static');
                        fetchNpmType('qs');
                    }
                }
            } catch (e) {}
        };

        loadDependencies();

        // 2. LIMPEZA NA DESMONTAGEM DA TELA
        return () => {
            loadedLibsRef.current.forEach(lib => {
                if (lib && typeof lib.dispose === 'function') lib.dispose();
            });
            loadedLibsRef.current = [];
        };
        // IGNORANDO O LINT E REMOVENDO listFiles E readFile DAQUI TAMBÉM:
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [monaco, safeFilePath]);

    const handleSave = async () => {
        if (!safeFilePath) return;

        setIsSaving(true);
        try {
            await writeFile(safeFilePath, content);
            toast.addToast("Arquivo salvo com sucesso!", "success");
        } catch (error) {
            toast.addToast("Erro ao salvar o arquivo.", "error");
            console.error("Erro ao salvar o arquivo:", error);
        } finally {
            setIsSaving(false);
        }
    };

    // Shortcut Ctrl+S
    useEffect(() => {
        const handleKeyDown = (e: any) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "s") {
                e.preventDefault();
                handleSave();
            }
        };

        window.addEventListener("keydown", handleKeyDown);
        return () => window.removeEventListener("keydown", handleKeyDown);
    }, [content, safeFilePath]);

    // Se estiver carregando o conteúdo, exibe o LoadingPage
    if (isLoading) {
        return (
            <AnimatePresence mode="wait">
                <motion.div
                    key="loading-editor"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{
                        duration: 0.35,
                        ease: [0.4, 0, 0.2, 1],
                    }}
                    className="absolute inset-0 flex flex-col items-center justify-center z-10 bg-[var(--color-secondary)]"
                >
                    <LoadingPage />
                </motion.div>
            </AnimatePresence>
        );
    }

    return (
        <div className="flex-1 flex flex-col p-4 md:p-6 overflow-hidden relative text-[var(--color-text-value)] h-full w-full">
            <div className="flex items-center justify-between mb-4 shrink-0">
                <div className="flex items-center gap-3 text-sm font-mono text-[var(--color-text-sub)]">
                    <button
                        onClick={closeEdit}
                        className="p-2 -ml-2 rounded-lg hover:bg-[var(--color-secondary)] hover:text-white transition cursor-pointer"
                        title="Fechar a edição"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </button>
                    <div className="flex items-center gap-1.5 flex-wrap">
                        <span className="text-[var(--color-text-sub)]/50 font-bold select-none">/</span>
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
            </div>

            <div className="flex-1 relative min-h-120 w-full rounded-t-xl overflow-visible shadow-[var(--card-shadow)] border border-[var(--color-terciary)] bg-[var(--color-console)]">
                <div className="absolute inset-0">
                    <Editor
                        path={safeFilePath ? `file://${safeFilePath}` : undefined}
                        height="100%"
                        language={language}
                        theme="vs-dark"
                        value={content}
                        onChange={(value) => setContent(value || "")}
                        onMount={handleEditorDidMount}
                        options={{
                            minimap: { enabled: false },
                            fontSize: 14,
                            fontFamily: 'var(--font-mono)',
                            wordWrap: "on",
                            scrollBeyondLastLine: false,
                            smoothScrolling: true,
                            cursorBlinking: "smooth",
                            padding: { top: 16, bottom: 16 },
                        }}
                    />
                </div>
            </div>

            <div className="flex items-center justify-end gap-3 pt-4 pb-2 shrink-0">
                <div className="relative">
                    <select
                        value={language}
                        onChange={(e) => setLanguage(e.target.value)}
                        className="appearance-none bg-(--color-terciary) text-(--color-text-label) font-medium text-sm rounded-md px-4 py-3 pr-10 outline-none focus:ring-2 focus:ring-[var(--color-primary)] cursor-pointer transition hover:brightness-110"
                    >
                        {SUPPORTED_LANGUAGES.map((lang) => (
                            <option key={lang.value} value={lang.value}>
                                {lang.label}
                            </option>
                        ))}
                    </select>
                    <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-[var(--color-text-sub)]">
                        <svg className="w-4 h-4 fill-current" viewBox="0 0 20 20">
                            <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                        </svg>
                    </div>
                </div>

                <Button
                    variant="info"
                    onClick={handleSave}
                    disabled={isSaving}
                    className="min-w-[160px]"
                >
                    {isSaving ? "Salvando..." : "Salvar"}
                </Button>
            </div>
        </div>
    );
}