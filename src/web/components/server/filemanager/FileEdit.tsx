import React, { useState, useEffect, useRef } from "react";
import Editor, { useMonaco } from "@monaco-editor/react";
import Button from "@/web/components/commons/components/Button";
import { ArrowLeft, Save, FileCode2, ChevronDown } from "lucide-react";
import { useFileManager } from "./FileManagerContext";
import { useServerContext } from "@/web/contexts/ServerContext";
import { useToast } from "@/web/contexts/ToastContext";
import LoadingPage from "@/web/components/commons/LoadingPage";
import { AnimatePresence, motion } from "framer-motion";

// === SUPORTE MASSIVO A LINGUAGENS ===
const SUPPORTED_LANGUAGES = [
    { value: "json", label: "JSON" },
    { value: "yaml", label: "YAML / YML" },
    { value: "properties", label: "Properties" },
    { value: "xml", label: "XML" },
    { value: "php", label: "PHP" },
    { value: "javascript", label: "JavaScript" },
    { value: "typescript", label: "TypeScript" },
    { value: "html", label: "HTML / Vue / Svelte" },
    { value: "css", label: "CSS" },
    { value: "scss", label: "SCSS / SASS" },
    { value: "python", label: "Python" },
    { value: "ruby", label: "Ruby" },
    { value: "java", label: "Java" },
    { value: "cpp", label: "C / C++" },
    { value: "csharp", label: "C#" },
    { value: "go", label: "Go" },
    { value: "rust", label: "Rust" },
    { value: "lua", label: "Lua" },
    { value: "sql", label: "SQL" },
    { value: "shell", label: "Shell Script (.sh)" },
    { value: "dockerfile", label: "Dockerfile" },
    { value: "ini", label: "INI / Config" },
    { value: "markdown", label: "Markdown" },
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

    const toast = useToast();
    const monaco = useMonaco();
    const server = useServerContext();

    // Guarda as tipagens e modelos do arquivo atual para matar elas ao trocar de arquivo
    const loadedLibsRef = useRef<any[]>([]);

    const safeFilePath = editingFilePath || "";
    const breadcrumbs = getBreadcrumbs(safeFilePath);

    // Define a linguagem com base na extensão (Agora com muito mais Regex)
    useEffect(() => {
        if (!safeFilePath) return;

        if (safeFilePath.endsWith(".json")) setLanguage("json");
        else if (safeFilePath.match(/\.(yml|yaml)$/i)) setLanguage("yaml");
        else if (safeFilePath.endsWith(".properties")) setLanguage("properties");
        else if (safeFilePath.endsWith(".xml")) setLanguage("xml");
        else if (safeFilePath.match(/\.(php|php4|php5|phtml)$/i)) setLanguage("php");
        else if (safeFilePath.match(/\.(js|jsx)$/i)) setLanguage("javascript");
        else if (safeFilePath.match(/\.(ts|tsx)$/i)) setLanguage("typescript");
        else if (safeFilePath.match(/\.(html|htm|vue|svelte)$/i)) setLanguage("html");
        else if (safeFilePath.match(/\.(css)$/i)) setLanguage("css");
        else if (safeFilePath.match(/\.(scss|sass)$/i)) setLanguage("scss");
        else if (safeFilePath.match(/\.(py)$/i)) setLanguage("python");
        else if (safeFilePath.match(/\.(rb)$/i)) setLanguage("ruby");
        else if (safeFilePath.match(/\.(java)$/i)) setLanguage("java");
        else if (safeFilePath.match(/\.(c|cpp|h|hpp)$/i)) setLanguage("cpp");
        else if (safeFilePath.match(/\.(cs)$/i)) setLanguage("csharp");
        else if (safeFilePath.match(/\.(go)$/i)) setLanguage("go");
        else if (safeFilePath.match(/\.(rs)$/i)) setLanguage("rust");
        else if (safeFilePath.match(/\.(lua)$/i)) setLanguage("lua");
        else if (safeFilePath.match(/\.(sql)$/i)) setLanguage("sql");
        else if (safeFilePath.match(/\.(sh|bash|command)$/i)) setLanguage("shell");
        else if (safeFilePath.match(/\.(ini|conf|cfg|config)$/i)) setLanguage("ini");
        else if (safeFilePath.match(/\.(md|markdown)$/i)) setLanguage("markdown");
        else if (safeFilePath.match(/Dockerfile/i)) setLanguage("dockerfile");
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
                    'editor.background': '#0f1419', // Fundo escuro
                    'editor.lineHighlightBackground': '#151b21',
                    'editor.inactiveSelectionBackground': '#25303a',
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
            allowJs: true,
            checkJs: true,
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

    // Lógica de dependências (Models locais e Libs NPM) - Mantida Intacta
    useEffect(() => {
        if (!monaco || !safeFilePath) return;

        const monacoAny = monaco as any;
        if (!safeFilePath.match(/\.(js|ts|jsx|tsx)$/)) return;

        loadedLibsRef.current.forEach(lib => {
            if (lib && typeof lib.dispose === 'function') lib.dispose();
        });
        loadedLibsRef.current = [];

        const loadDependencies = async () => {
            const dirPath = safeFilePath.substring(0, safeFilePath.lastIndexOf('/'));
            if (!dirPath) return;

            const addLib = (content: string, uri: string) => {
                try {
                    const jsLib = monacoAny.languages.typescript.javascriptDefaults.addExtraLib(content, uri);
                    const tsLib = monacoAny.languages.typescript.typescriptDefaults.addExtraLib(content, uri);
                    loadedLibsRef.current.push(jsLib, tsLib);
                } catch (e) { }
            };

            const addLocalModel = (content: string, filePath: string) => {
                try {
                    const uri = monacoAny.Uri.file(filePath);
                    let model = monacoAny.editor.getModel(uri);
                    if (!model) {
                        model = monacoAny.editor.createModel(content, undefined, uri);
                        loadedLibsRef.current.push(model);
                    } else {
                        model.setValue(content);
                    }
                } catch (e) { }
            };

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

        return () => {
            loadedLibsRef.current.forEach(lib => {
                if (lib && typeof lib.dispose === 'function') lib.dispose();
            });
            loadedLibsRef.current = [];
        };
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

    // Loading State Modernizado
    if (isLoading) {
        return (
            <AnimatePresence mode="wait">
                <motion.div
                    key="loading-editor"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 0.35, ease: [0.4, 0, 0.2, 1] }}
                    className="absolute inset-0 flex flex-col items-center justify-center z-10 bg-[var(--color-secondary)]"
                >
                    <LoadingPage />
                </motion.div>
            </AnimatePresence>
        );
    }

    return (
        <div className="flex-1 flex flex-col p-6 overflow-hidden relative text-[var(--color-text-value)] h-full w-full gap-5">

            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 shrink-0">
                <div className="flex items-center gap-4 text-[13px] font-mono text-[var(--color-text-sub)] bg-[var(--color-terciary)] border border-white/5 px-3 py-2 rounded-xl shadow-sm">
                    <button
                        onClick={closeEdit}
                        className="p-1.5 -ml-1 rounded-lg bg-white/5 hover:bg-[var(--color-primary)] hover:text-[#09090b] transition-all cursor-pointer border border-white/5"
                        title="Voltar"
                    >
                        <ArrowLeft className="w-4 h-4" />
                    </button>
                    <div className="flex items-center gap-1.5 flex-wrap">
                        <span className="opacity-50 font-bold select-none">/</span>
                        {breadcrumbs.map((crumb, index) => (
                            <React.Fragment key={crumb.path}>
                                <span
                                    className={`cursor-pointer transition ${crumb.isBase ? 'hover:text-[var(--color-text-value)]' : 'text-[var(--color-primary)] font-bold hover:text-[var(--color-text-value)]'}`}
                                    onClick={() => {
                                        if(index === breadcrumbs.length - 1) return;
                                        navigateToPath(crumb.path);
                                    }}
                                >
                                    {crumb.name}
                                </span>
                                {index < breadcrumbs.length - 1 && <span className="opacity-50">/</span>}
                            </React.Fragment>
                        ))}
                    </div>
                </div>

                {/* Footer/Ações embutidos no Header para otimizar espaço */}
                <div className="flex items-center gap-3">
                    <div className="relative">
                        <select
                            value={language}
                            onChange={(e) => setLanguage(e.target.value)}
                            className="appearance-none bg-[var(--color-terciary)] border border-white/5 text-[var(--color-text-value)] font-bold text-[12px] uppercase tracking-wider rounded-xl px-4 py-3 pr-10 outline-none focus:ring-2 focus:ring-[var(--color-primary)] cursor-pointer transition-all shadow-sm"
                        >
                            {SUPPORTED_LANGUAGES.map((lang) => (
                                <option key={lang.value} value={lang.value}>
                                    {lang.label}
                                </option>
                            ))}
                        </select>
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-[var(--color-text-sub)]">
                            <ChevronDown className="w-4 h-4" />
                        </div>
                    </div>

                    <Button
                        variant="info"
                        onClick={handleSave}
                        disabled={isSaving}
                        className="!py-2.5 !px-5 !text-[13px] flex items-center gap-2"
                    >
                        <Save className="w-4 h-4" />
                        {isSaving ? "Salvando..." : "Salvar"}
                    </Button>
                </div>
            </div>

            {/* Container do Monaco Editor com bordas do Design System */}
            <div className="flex-1 relative min-h-120 w-full rounded-2xl overflow-hidden shadow-[var(--card-shadow)] border border-white/5 bg-[#0f1419]">
                <div className="absolute inset-0 pt-2">
                    <Editor
                        path={safeFilePath ? `file://${safeFilePath}` : undefined}
                        height="100%"
                        language={language}
                        theme="pterodactyl-dark"
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
        </div>
    );
}