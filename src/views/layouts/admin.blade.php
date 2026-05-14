<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hight Cloud - @yield('title', 'Admin')</title>

    <!-- Scripts e Fontes -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="/assets/admin/modal.js"></script>
    <link rel="icon" href="/assets/img/logo-white.png">

    <style>
        :root {
            /* Fontes */
            --font-inter: "Inter", ui-sans-serif, system-ui, sans-serif;

            /* Cores Estritas do Print Escuro (Pelican Dark / H4U) */
            --color-primary: #3b82f6; /* Azul de destaque */

            /* Fundo principal e layout */
            --color-background: #09090b;
            --color-sidebar: #09090b;
            --color-navbar: #09090b;

            /* Elementos sobrepostos */
            --color-secondary: #18181b; /* Fundo Hover e Cards */
            --color-terciary: #27272a;  /* Bordas super sutis */

            /* Cores de Texto */
            --color-text-value: #ffffff; /* Títulos, Links Ativos e Logo */
            --color-text-label: #a1a1aa; /* Textos de links inativos */
            --color-text-sub: #71717a;   /* Categorias */

            /* Feedback */
            --color-success: #22c55e;
            --color-danger: #ef4444;
            --color-warning: #eab308;
            --color-info: #3b82f6;

            /* Sombras sutis pro tema dark */
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5), 0 2px 4px -1px rgba(0, 0, 0, 0.3);
        }

        body {
            background-color: var(--color-background);
            color: var(--color-text-value);
            font-family: var(--font-inter);
            margin: 0;
            padding: 0;
        }

        /* Loading Bar Global */
        #top-loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background-color: var(--color-primary);
            width: 0%;
            z-index: 9999;
            transition: width 0.3s ease, opacity 0.3s ease;
            opacity: 0;
            pointer-events: none;
        }

        /* Estrutura Sidebar e Content */
        .admin-sidebar {
            width: 260px;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: var(--color-sidebar);
            border-right: 1px solid var(--color-terciary);
        }

        .admin-content {
            margin-left: 260px;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: var(--color-background);
        }

        /* Comportamento da Sidebar Recolhida */
        body.sidebar-collapsed .admin-sidebar { width: 72px; }
        body.sidebar-collapsed .admin-content { margin-left: 72px; }

        body.sidebar-collapsed .sidebar-text,
        body.sidebar-collapsed .sidebar-logo-text,
        body.sidebar-collapsed .nav-badge,
        body.sidebar-collapsed .sidebar-category-header { display: none; }

        /* Centralizando logo icone quando fechado */
        body.sidebar-collapsed .sidebar-logo-container { justify-content: center; padding: 0; }
        body.sidebar-collapsed .sidebar-logo-icon { display: flex !important; }

        /* Links quando fechados */
        body.sidebar-collapsed .nav-link {
            justify-content: center;
            padding: 12px 0;
            margin: 2px 10px;
        }
        body.sidebar-collapsed .nav-link svg { margin: 0; }

        /* Títulos de Categoria */
        .sidebar-category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 20px 8px 20px;
            color: var(--color-text-sub);
            font-size: 0.75rem;
            font-weight: 600;
            cursor: default;
        }

        /* Links da Sidebar */
        .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            margin: 2px 12px;
            border-radius: 8px;
            color: var(--color-text-label);
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            gap: 14px;
            transition: all 0.2s;
        }

        .nav-link svg {
            color: var(--color-text-sub);
            transition: color 0.2s;
        }

        /* Hover */
        .nav-link:hover:not(.active) {
            color: var(--color-text-value);
            background-color: var(--color-secondary);
        }
        .nav-link:hover:not(.active) svg {
            color: var(--color-text-label);
        }

        /* Ativo */
        .nav-link.active {
            background-color: var(--color-secondary);
            color: var(--color-primary);
        }
        .nav-link.active svg {
            color: var(--color-primary);
        }

        /* Badge Lateral */
        .nav-badge {
            margin-left: auto;
            background-color: rgba(234, 179, 8, 0.1);
            color: var(--color-warning);
            border: 1px solid rgba(234, 179, 8, 0.2);
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            line-height: 1;
        }

        /* Scrollbar Minimalista */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--color-terciary); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--color-text-sub); }

        ::selection {
            background-color: rgba(59, 130, 246, 0.3);
            color: #ffffff;
        }
    </style>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: 'var(--color-primary)',
                        bgBase: 'var(--color-background)',
                        cards: 'var(--color-secondary)',
                        terciary: 'var(--color-terciary)',
                        navbar: 'var(--color-navbar)',
                        sidebar: 'var(--color-sidebar)',
                        textLabel: 'var(--color-text-label)',
                        textValue: 'var(--color-text-value)',
                        textSub: 'var(--color-text-sub)',
                        success: 'var(--color-success)',
                        danger: 'var(--color-danger)',
                        warning: 'var(--color-warning)',
                        info: 'var(--color-info)',
                    },
                    boxShadow: {
                        main: 'var(--card-shadow)'
                    }
                }
            }
        }
    </script>

    <script>
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>
</head>
<body class="antialiased overflow-x-hidden selection:bg-primary/30 selection:text-textValue">

<!-- Barinha de loading fluida -->
<div id="top-loading-bar"></div>

<!-- SIDEBAR -->
<aside class="admin-sidebar flex flex-col z-30 fixed left-0 top-0 h-screen">
    <!-- Logo -->
    <div class="h-16 flex items-center shrink-0 border-b border-terciary">
        <a href="/" class="flex items-center justify-center gap-3 w-full px-6 sidebar-logo-container transition-all">
            @php
                $companyName = \Vatts\Vatts::getEnv("COMPANY_NAME", "Hight Cloud");
                $initials = implode('', array_map(
                fn($word) => strtoupper($word[0]),
                preg_split('/\s+/', trim($companyName))
            ));
            @endphp
            <span class="sidebar-logo-icon hidden text-[20px] font-black text-textValue tracking-tighter">{{ $initials }}</span>
            <span class="sidebar-logo-text text-[15px] font-bold tracking-wide text-textValue whitespace-nowrap">{{ $companyName }}</span>
        </a>
    </div>

    <!-- Navegação e Categorias -->
    <nav class="flex-1 overflow-y-auto custom-scrollbar flex flex-col py-4 gap-0.5 pb-4">
        <!-- Dashboard -->
        <a href="/admin" class="nav-link {{ (isset($request) && $request->is('/admin')) ? 'active' : '' }}" title="Dashboard">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
            <span class="sidebar-text whitespace-nowrap">Dashboard</span>
        </a>
        <a href="/admin/settings" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/settings')) ? 'active' : '' }}" title="Config Options">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
            <span class="sidebar-text whitespace-nowrap">Configurações</span>
        </a>
        <a href="/admin/tokens" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/tokens')) ? 'active' : '' }}" title="API Tokens">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            <span class="sidebar-text whitespace-nowrap">API Tokens</span>
        </a>

        <!-- Administration -->
        <div class="sidebar-category-header">
            <span>Administration</span>
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m18 15-6-6-6 6"/></svg>
        </div>

        <a href="/admin/users" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/users')) ? 'active' : '' }}" title="Users">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span class="sidebar-text whitespace-nowrap">Users</span>
        </a>

        <a href="/admin/database-hosts" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/database-hosts')) ? 'active' : '' }}" title="Database Hosts">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
            <span class="sidebar-text whitespace-nowrap">Database Hosts</span>
        </a>

        <!-- Configuration -->
        <div class="sidebar-category-header">
            <span>Gerenciador de serviços</span>
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m18 15-6-6-6 6"/></svg>
        </div>


        <a href="/admin/cores" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/cores')) ? 'active' : '' }}" title="Custom Properties">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
            <span class="sidebar-text whitespace-nowrap">Cores</span>
        </a>

        <!-- Extensions -->
        <div class="sidebar-category-header">
            <span>Gerenciamento</span>
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m18 15-6-6-6 6"/></svg>
        </div>

        <a href="/admin/servers" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/servers')) ? 'active' : '' }}" title="Servers">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
            <span class="sidebar-text whitespace-nowrap">Servers</span>
        </a>

        <a href="/admin/nodes" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/nodes')) ? 'active' : '' }}" title="Nodes">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            <span class="sidebar-text whitespace-nowrap">Nodes</span>
        </a>
    </nav>
</aside>

<div class="admin-content flex flex-col relative min-h-screen">
    <!-- Navbar Superior -->
    <header class="h-16 px-6 flex items-center justify-between sticky top-0 bg-navbar z-20 border-b border-terciary">
        <div class="flex items-center gap-6">
            <button id="sidebarToggle" class="text-textLabel hover:text-textValue transition-colors" title="Alternar Menu">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>
        </div>

        <div class="flex items-center gap-4">
            <!-- Barra de Pesquisa Rápida -->
            @if(isset($resources) || isset($showSearch))
                <form action="" method="GET" class="relative group mr-2 hidden md:block">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-textLabel">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>
                    </div>
                    <input
                            type="text"
                            name="search"
                            value="{{ isset($request) && isset($request->getQuery()['search']) ? $request->getQuery()['search'] : '' }}"
                            placeholder="Buscar..."
                            class="bg-black/20 rounded-md pl-9 pr-3 py-1.5 text-[13px] w-48 focus:w-64 outline-none transition-all text-textValue placeholder-textLabel border border-white/5 focus:border-primary/50 shadow-inner"
                    >
                </form>
            @endif

            <!-- Usuário com Gravatar -->
            <div class="flex items-center gap-3 pl-4 border-l border-terciary">
                <img src="https://www.gravatar.com/avatar/{{ md5(strtolower(trim($user->email ?? ''))) }}?s=80&d=mp" alt="Avatar" class="w-8 h-8 rounded-full border border-terciary">
                <span class="text-[13px] font-semibold text-textValue hidden md:block">{{ $user->first_name ?? 'Admin' }}</span>
            </div>

            <!-- Ações -->
            <div class="flex items-center gap-1">
                <a href="/" class="p-2 text-textLabel hover:text-textValue hover:bg-white/5 rounded-md transition-colors" title="Painel do Cliente">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                </a>

                <form action="/logout" method="POST" class="m-0 p-0">
                    <button type="submit" class="p-2 text-textLabel hover:text-danger hover:bg-danger/10 rounded-md transition-colors" title="Sair">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="p-8 md:p-10 flex-1">
        <!-- Alertas de Feedback -->
        @if(isset($success))
            <div class="mb-8 p-4 rounded-lg bg-success/10 text-success text-[13px] font-bold flex items-center gap-3 border border-success/20">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                {{ $success }}
            </div>
        @endif
        @if(isset($error))
            <div class="mb-8 p-4 rounded-lg bg-danger/10 text-danger text-[13px] font-bold flex items-center gap-3 border border-danger/20">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                {{ $error }}
            </div>
        @endif

        <!-- ONDE ENTRA TODO O SEU CONTEÚDO ORIGINAL VIA BLADE YIELD -->
        @yield('content')
    </main>

    <!-- FOOTER ALINHADO À ESQUERDA -->
    <footer class="mt-auto py-6 px-8 border-t border-terciary text-left text-[12px] text-textSub bg-background">
        <p>&copy; {{ date('Y') }} {{ \Vatts\Vatts::getEnv("COMPANY_NAME", "Hight Cloud") }}. Todos os direitos reservados.</p>
    </footer>
</div>

<script notRepeat="true">
    document.addEventListener('DOMContentLoaded', () => {
        // --- 1. Lógica original da Sidebar (Agora apenas o botão principal) ---
        const toggleBtn = document.querySelector('#sidebarToggle');

        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }

        if(toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-collapsed');
                const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', isCollapsed);
            });
        }

        // --- 2. Lógica nova: SPA Loading via Fetch ---
        document.addEventListener('click', async (e) => {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href');

            if (!href || href.startsWith('http') || href.startsWith('#') || link.target === '_blank' || !href.includes("admin") || href.includes("create")) return;

            e.preventDefault();

            const loadingBar = document.getElementById('top-loading-bar');
            loadingBar.style.opacity = '1';
            loadingBar.style.width = '30%';

            try {
                setTimeout(() => { if(loadingBar.style.width === '30%') loadingBar.style.width = '60%'; }, 200);

                const response = await fetch(href);
                if (!response.ok) throw new Error('Erro na requisição da página');
                const html = await response.text();

                loadingBar.style.width = '90%';

                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newMain = doc.querySelector('main');
                if (newMain) {
                    document.querySelector('main').innerHTML = newMain.innerHTML;

                    const scripts = document.querySelector('main').querySelectorAll('script');

                    scripts.forEach(script => {

                        if (script.src) {
                            // Scripts externos precisam ser injetados para o navegador baixar
                            const newScript = document.createElement('script');
                            newScript.src = script.src;
                            document.body.appendChild(newScript);
                        } else if(script.getAttribute('notRepeat') !== 'true') {

                            window.eval(script.textContent);
                        }
                    });
                }

                if (doc.title) {
                    document.title = doc.title;
                }

                window.history.pushState({}, '', href);

                document.querySelectorAll('.nav-link').forEach(nav => {
                    nav.classList.remove('active');
                    if(nav.getAttribute('href') === href) {
                        nav.classList.add('active');
                    }
                });

                loadingBar.style.width = '100%';
                setTimeout(() => {
                    loadingBar.style.opacity = '0';
                    setTimeout(() => { loadingBar.style.width = '0%'; }, 300);
                }, 300);

            } catch (error) {
                console.error('Falha no SPA Fetch. Fallback ativado:', error);
                window.location.href = href;
            }
        });

        window.addEventListener('popstate', () => {
            window.location.reload();
        });
    });

    // --- 3. CORREÇÃO DO ERRO 'handleRowClick' NAS TABELAS ---
    window.handleRowClick = function(url) {
        const linkFalso = document.createElement('a');
        linkFalso.href = url;

        const clique = new MouseEvent('click', { bubbles: true, cancelable: true, view: window });
        linkFalso.dispatchEvent(clique);
    };
</script>
</body>
</html>