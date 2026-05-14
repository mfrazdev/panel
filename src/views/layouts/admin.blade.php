<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lunar Panel | @yield('title', 'Admin')</title>

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
            --color-primary: rgb(156 59 246); /* Azul de destaque */

            /* Fundo principal e layout */
            --color-background: #000000;
            --color-sidebar: #000000;
            --color-navbar: #000000;

            /* Elementos sobrepostos */
            --color-secondary: #18181b; /* Fundo Hover e Cards */
            --color-terciary: #27272a;  /* Detalhes sutis sem ser borda */

            /* Cores de Texto */
            --color-text-value: #ffffff; /* Títulos, Links Ativos e Logo */
            --color-text-label: #a1a1aa; /* Textos de links inativos */
            --color-text-sub: #71717a;   /* Categorias */

            /* Feedback */
            --color-success: #22c55e;
            --color-danger: #ef4444;
            --color-warning: #eab308;
            --color-info: #3b82f6;

            /* Sombras pro tema dark (Sem bordas, focado na sombra) */
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

        /* Estrutura Sidebar e Content (0 bordas) */
        .admin-sidebar {
            width: 260px;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: var(--color-sidebar);
            box-shadow: var(--card-shadow);
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
        body.sidebar-collapsed .sidebar-category-header,
        body.sidebar-collapsed .sidebar-search-container { display: none; }

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
            box-shadow: var(--card-shadow);
        }
        .nav-link:hover:not(.active) svg {
            color: var(--color-text-label);
        }

        /* Ativo */
        .nav-link.active {
            background-color: var(--color-secondary);
            color: var(--color-primary);
            box-shadow: var(--card-shadow);
        }
        .nav-link.active svg {
            color: var(--color-primary);
        }

        /* Badge Lateral (Sem bordas, apenas background sutil) */
        .nav-badge {
            margin-left: auto;
            background-color: rgba(234, 179, 8, 0.15);
            color: var(--color-warning);
            font-size: 0.65rem;
            font-weight: 700;
            padding: 3px 6px;
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
    <div class="h-16 flex items-center shrink-0">
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

    <!-- Barra de Pesquisa na Sidebar -->
    <div class="px-4 py-2 sidebar-search-container shrink-0">
        <form action="" method="GET" class="relative group w-full">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-textLabel">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>
            </div>
            <input
                    type="text"
                    name="search"
                    value="{{ isset($request) && isset($request->getQuery()['search']) ? $request->getQuery()['search'] : '' }}"
                    placeholder="Buscar..."
                    class="bg-white/5 rounded-lg pl-9 pr-3 py-2 text-[13px] w-full outline-none transition-all text-textValue placeholder-textLabel focus:bg-white/10 shadow-inner"
            >
        </form>
    </div>

    <!-- Navegação e Categorias -->
    <nav class="flex-1 overflow-y-auto custom-scrollbar flex flex-col py-2 gap-0.5 pb-4">
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
            <span class="sidebar-text whitespace-nowrap">Tokens de API</span>
        </a>

        <!-- Administration -->
        <div class="sidebar-category-header">
            <span>Administração</span>
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m18 15-6-6-6 6"/></svg>
        </div>

        <a href="/admin/users" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/users')) ? 'active' : '' }}" title="Users">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span class="sidebar-text whitespace-nowrap">Usuários</span>
        </a>

        <a href="/admin/database-hosts" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/database-hosts')) ? 'active' : '' }}" title="Database Hosts">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
            <span class="sidebar-text whitespace-nowrap">Servidores MySQL</span>
        </a>

        <!-- Configuration -->
        <div class="sidebar-category-header">
            <span>Gerenciador de serviços</span>
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m18 15-6-6-6 6"/></svg>
        </div>

        <a href="/admin/cores" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/cores')) ? 'active' : '' }}" title="Custom Properties">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cpu-icon lucide-cpu shrink-0"><path d="M12 20v2"/><path d="M12 2v2"/><path d="M17 20v2"/><path d="M17 2v2"/><path d="M2 12h2"/><path d="M2 17h2"/><path d="M2 7h2"/><path d="M20 12h2"/><path d="M20 17h2"/><path d="M20 7h2"/><path d="M7 20v2"/><path d="M7 2v2"/><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>
            <span class="sidebar-text whitespace-nowrap">Cores</span>
        </a>

        <!-- Extensions -->
        <div class="sidebar-category-header">
            <span>Gerenciamento</span>
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m18 15-6-6-6 6"/></svg>
        </div>

        <a href="/admin/servers" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/servers')) ? 'active' : '' }}" title="Servers">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
            <span class="sidebar-text whitespace-nowrap">Servidores</span>
        </a>

        <a href="/admin/nodes" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/nodes')) ? 'active' : '' }}" title="Nodes">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            <span class="sidebar-text whitespace-nowrap">Nodes</span>
        </a>
    </nav>

    <!-- User Profile Dropdown na Sidebar (Z-index top e visibilidade baseada em classes) -->
    <div class="relative shrink-0 mt-auto" id="sidebar-user-container">

        <!-- Dropdown Menu -->
        <div id="user-dropdown" class="absolute bottom-full left-0 p-2 mb-2 z-[9999] w-[250px] transition-all duration-200 opacity-0 pointer-events-none translate-y-2">
            <div class="bg-terciary rounded-xl shadow-main flex flex-col py-1">
                <a href="/admin/profile" class="px-4 py-3 text-sm font-medium text-textLabel hover:text-textValue hover:bg-white/5 transition-colors flex items-center gap-3 border-b border-white/10">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span class="sidebar-text">Perfil</span>
                </a>
                <a href="/" class="px-4 py-3 text-sm font-medium text-textLabel hover:text-textValue hover:bg-white/5 transition-colors flex items-center gap-3">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                    <span class="sidebar-text">Sair do Admin</span>
                </a>
                <form  method="POST" class="m-0 p-0"id="signout">
                    <button type="submit" class="w-full text-left px-4 py-3 text-sm font-medium text-textLabel hover:text-textValue hover:bg-white/5 transition-colors flex items-center gap-3">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        <span class="sidebar-text">Logout</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Botão Toggle do Usuário -->
        <button id="user-dropdown-toggle" class="w-full flex items-center justify-between gap-3 p-4 hover:bg-secondary transition-colors cursor-pointer text-left focus:outline-none">
            <div class="flex items-center gap-3 overflow-hidden">
                <img src="https://www.gravatar.com/avatar/{{ md5(strtolower(trim($user->email ?? ''))) }}?s=80&d=mp" alt="Avatar" class="w-8 h-8 rounded-full shrink-0 shadow-sm">
                <span class="text-[14px] font-semibold text-textValue truncate sidebar-text">{{ $user->first_name ?? 'admin' }}</span>
            </div>
            <svg id="user-dropdown-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="text-textSub sidebar-text shrink-0 transition-transform duration-200"><path d="m18 15-6-6-6 6"/></svg>
        </button>
    </div>
</aside>

<div class="admin-content flex flex-col relative min-h-screen">

    <main class="p-8 md:p-10 flex-1">
        <!-- Alertas de Feedback -->
        @if(isset($success))
            <div class="mb-8 p-4 rounded-lg bg-success/10 text-success text-[13px] font-bold flex items-center gap-3 shadow-main">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                {{ $success }}
            </div>
        @endif
        @if(isset($error))
            <div class="mb-8 p-4 rounded-lg bg-danger/10 text-danger text-[13px] font-bold flex items-center gap-3 shadow-main">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                {{ $error }}
            </div>
        @endif

        <!-- ONDE ENTRA TODO O SEU CONTEÚDO ORIGINAL VIA BLADE YIELD -->
        @yield('content')
    </main>

    <!-- FOOTER ALINHADO À ESQUERDA -->
    <footer class="mt-auto py-6 px-8 text-left text-[12px] text-textSub bg-background shadow-inner">
        <p>&copy; {{ date('Y') }} <a href="https://hight.cloud" class="hover:text-textValue transition-all">Lunar Panel</a>. Todos os direitos reservados.</p>
    </footer>
</div>

<script notRepeat="true">
    document.addEventListener('DOMContentLoaded', () => {
        // --- 1. Lógica Refeita do Dropdown do Usuário ---
        const userToggle = document.getElementById('user-dropdown-toggle');
        const userDropdown = document.getElementById('user-dropdown');
        const userIcon = document.getElementById('user-dropdown-icon');

        if (userToggle && userDropdown) {
            userToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                // Alterna as classes de visibilidade e animação
                userDropdown.classList.toggle('opacity-0');
                userDropdown.classList.toggle('pointer-events-none');
                userDropdown.classList.toggle('translate-y-2');

                // Rotaciona a setinha pra ficar charmoso
                userIcon.classList.toggle('rotate-180');
            });

            // Fecha o dropdown se clicar fora
            document.addEventListener('click', (e) => {
                if (!userToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                    userIcon.classList.remove('rotate-180');
                }
            });
        }

        // --- Atalho de Teclado (Ctrl+B / Cmd+B) para a Sidebar ---
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }

        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                e.preventDefault();
                document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', document.body.classList.contains('sidebar-collapsed'));
            }
        });


        const signoutForm = document.getElementById('signout');
        if (signoutForm) {
            signoutForm.addEventListener('submit', (e) => {
                e.preventDefault();
                fetch('/api/auth/signout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                }).then(response => {
                    if (response.ok) {
                        window.location.href = '/auth';
                    } else {
                        alert('Erro ao fazer logout. Tente novamente.');
                    }
                }).catch(() => {
                    alert('Erro de rede. Tente novamente.');
                });
            })
        }
    })





</script>
</body>
</html>