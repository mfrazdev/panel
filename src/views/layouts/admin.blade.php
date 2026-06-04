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
            --font-inter: "Inter", ui-sans-serif, system-ui, sans-serif;
            --color-primary: rgb(156 59 246); /* Azul/Roxo de destaque */

            /* Fundo principal PRETO com brilho super sutil */
            --color-background: #000000;
            --color-secondary: rgba(24, 24, 27, 0.4);
            --color-terciary: rgba(39, 39, 42, 0.6);

            /* Cores de Texto */
            --color-text-value: #ffffff;
            --color-text-label: #a1a1aa;
            --color-text-sub: #71717a;

            /* Feedback */
            --color-success: #22c55e;
            --color-danger: #ef4444;
            --color-warning: #eab308;
            --color-info: #3b82f6;

            /* Sombra principal limpa, ZERO BORDAS */
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.8), 0 8px 10px -6px rgba(0, 0, 0, 0.6);
        }

        body {
            background-color: var(--color-background);
            /* Gradiente beeeem sutil só no topo esquerdo, misturando com o preto absoluto */
            background-image: radial-gradient(circle at 0% 0%, rgba(156, 59, 246, 0.07) 0%, transparent 40%);
            color: var(--color-text-value);
            font-family: var(--font-inter);
            margin: 0;
            padding: 0;
            min-height: 100vh;
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
            box-shadow: 0 0 12px var(--color-primary);
        }

        /* Estrutura Sidebar - Maior, Sem BG, Sem Borda */
        .admin-sidebar {
            width: 280px;
            transition: width 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            background: transparent; /* Removido o bg fixo */
        }

        .admin-content {
            margin-left: 280px;
            transition: margin-left 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Comportamento da Sidebar Recolhida */
        body.sidebar-collapsed .admin-sidebar { width: 76px; }
        body.sidebar-collapsed .admin-content { margin-left: 76px; }

        body.sidebar-collapsed .sidebar-text,
        body.sidebar-collapsed .sidebar-logo-text,
        body.sidebar-collapsed .nav-badge,
        body.sidebar-collapsed .item-count,
        body.sidebar-collapsed .sidebar-category-header,
        body.sidebar-collapsed .sidebar-search-container { display: none; }

        body.sidebar-collapsed .sidebar-logo-container { justify-content: center; padding: 0; }
        body.sidebar-collapsed .sidebar-logo-icon { display: flex !important; }

        body.sidebar-collapsed .nav-link {
            justify-content: center;
            padding: 12px 0;
            margin: 2px 12px;
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
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: default;
        }

        /* Animação de entrada fluida */
        @keyframes fadeSlideIn {
            0% { opacity: 0; transform: translateX(-10px); }
            100% { opacity: 1; transform: translateX(0); }
        }

        /* Links da Sidebar */
        .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            margin: 4px 16px;
            border-radius: 10px;
            color: var(--color-text-label);
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            gap: 14px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            animation: fadeSlideIn 0.5s ease-out forwards;
            opacity: 0; /* Começa invisível pra animação */
        }

        /* Delay nas animações para efeito cascata */
        .nav-link:nth-child(1) { animation-delay: 0.05s; }
        .nav-link:nth-child(2) { animation-delay: 0.1s; }
        .nav-link:nth-child(3) { animation-delay: 0.15s; }
        .sidebar-category-header:nth-child(4) { animation: fadeSlideIn 0.5s ease-out 0.2s forwards; opacity: 0; }
        .nav-link:nth-child(5) { animation-delay: 0.25s; }
        .nav-link:nth-child(6) { animation-delay: 0.3s; }
        .sidebar-category-header:nth-child(7) { animation: fadeSlideIn 0.5s ease-out 0.35s forwards; opacity: 0; }
        .nav-link:nth-child(8) { animation-delay: 0.4s; }
        .sidebar-category-header:nth-child(9) { animation: fadeSlideIn 0.5s ease-out 0.45s forwards; opacity: 0; }
        .nav-link:nth-child(10) { animation-delay: 0.5s; }
        .nav-link:nth-child(11) { animation-delay: 0.55s; }

        .nav-link svg {
            color: var(--color-text-sub);
            transition: all 0.3s ease;
        }

        /* Hover Dinâmico */
        .nav-link:hover:not(.active) {
            color: var(--color-text-value);
            background-color: rgba(255, 255, 255, 0.04);
            transform: translateX(4px); /* Efeito de mover levemente pro lado */
        }
        .nav-link:hover:not(.active) svg {
            color: var(--color-text-value);
            transform: scale(1.1);
        }

        /* Link Ativo */
        .nav-link.active {
            background: linear-gradient(135deg, rgba(156,59,246,0.15) 0%, rgba(156,59,246,0.02) 100%);
            color: var(--color-primary);
            box-shadow: var(--card-shadow);
        }
        .nav-link.active svg {
            color: var(--color-primary);
        }

        /* Contadores de Itens nas Categorias */
        .item-count {
            margin-left: auto;
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--color-text-sub);
            font-size: 0.65rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 12px; /* Borda bem redonda estilo badge moderno */
            line-height: 1;
            transition: all 0.3s;
        }
        .nav-link:hover .item-count {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--color-text-value);
        }
        .nav-link.active .item-count {
            background-color: rgba(156,59,246,0.2);
            color: var(--color-primary);
        }

        /* Input animado */
        #searchInput:focus {
            box-shadow: 0 0 15px rgba(156, 59, 246, 0.15);
            transform: translateY(-1px);
        }

        /* Scrollbar Minimalista */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.15); }

        ::selection {
            background-color: rgba(156, 59, 246, 0.3);
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
</head>
<body class="antialiased overflow-x-hidden selection:bg-primary/30 selection:text-textValue">

<div id="top-loading-bar"></div>

<!-- SIDEBAR MAIOR, SEM BG E SEM BORDA -->
<aside class="admin-sidebar z-30 fixed left-0 top-0 bottom-0 h-screen flex flex-col">

    <!-- Logo -->
    <div class="h-20 flex items-center shrink-0">
        <a href="/" class="flex items-center justify-center gap-3 w-full px-8 sidebar-logo-container transition-all hover:scale-105 duration-300">
            @php
            $companyName = \Vatts\Vatts::getEnv("COMPANY_NAME", "Hight Cloud");
            $initials = implode('', array_map(
            fn($word) => strtoupper($word[0]),
            preg_split('/\s+/', trim($companyName))
            ));
            @endphp
            <span class="sidebar-logo-icon hidden text-[22px] font-black text-primary tracking-tighter drop-shadow-lg">{{ $initials }}</span>
            <span class="sidebar-logo-text text-[16px] font-bold tracking-wider text-textValue whitespace-nowrap">{{ $companyName }}</span>
        </a>
    </div>

    <!-- Barra de Pesquisa Animada -->
    <div class="px-6 py-2 sidebar-search-container shrink-0">
        <form action="" method="GET" class="relative group w-full">
            <div class="bg-white/[0.03] hover:bg-white/[0.06] rounded-xl relative flex items-center transition-all duration-300">
                <div class="absolute left-3 text-textSub group-focus-within:text-primary transition-colors">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>
                </div>
                <input
                        id="searchInput"
                        type="text"
                        name="search"
                        value="{{ isset($request) && isset($request->getQuery()['search']) ? $request->getQuery()['search'] : '' }}"
                        placeholder="Buscar... (Ctrl+F)"
                        class="bg-transparent pl-9 pr-3 py-2.5 text-[13px] w-full outline-none transition-all text-textValue placeholder-textSub font-medium"
                >
            </div>
        </form>
    </div>

    <!-- Navegação e Categorias -->
    <nav class="flex-1 overflow-y-auto custom-scrollbar flex flex-col py-4 gap-1 pb-4">
        <!-- Dashboard -->
        <a href="/admin" class="nav-link {{ (isset($request) && $request->is('/admin')) ? 'active' : '' }}" title="Dashboard">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
            <span class="sidebar-text whitespace-nowrap">Dashboard</span>
        </a>
        <a href="/admin/settings" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/settings')) ? 'active' : '' }}" title="Configurações">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
            <span class="sidebar-text whitespace-nowrap">Configurações</span>
        </a>
        <a href="/admin/tokens" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/tokens')) ? 'active' : '' }}" title="Tokens de API">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            <span class="sidebar-text whitespace-nowrap">Tokens de API</span>
        </a>

        <!-- Administration -->
        <div class="sidebar-category-header">
            <span>Administração</span>
        </div>

        <a href="/admin/users" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/users')) ? 'active' : '' }}" title="Usuários">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span class="sidebar-text whitespace-nowrap">Usuários</span>
            <!-- Contador para o Back-end preencher -->
            <span class="item-count">{{ $usersCount ?? 0 }}</span>
        </a>

        <a href="/admin/database-hosts" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/database-hosts')) ? 'active' : '' }}" title="Servidores MySQL">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
            <span class="sidebar-text whitespace-nowrap">Servidores MySQL</span>
            <span class="item-count">{{ $databasesCount ?? 0 }}</span>
        </a>

        <!-- Configuration -->
        <div class="sidebar-category-header">
            <span>Gerenciador</span>
        </div>

        <a href="/admin/cores" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/cores')) ? 'active' : '' }}" title="Cores">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cpu-icon lucide-cpu shrink-0"><path d="M12 20v2"/><path d="M12 2v2"/><path d="M17 20v2"/><path d="M17 2v2"/><path d="M2 12h2"/><path d="M2 17h2"/><path d="M2 7h2"/><path d="M20 12h2"/><path d="M20 17h2"/><path d="M20 7h2"/><path d="M7 20v2"/><path d="M7 2v2"/><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>
            <span class="sidebar-text whitespace-nowrap">Cores</span>
            <span class="item-count">{{ $coresCount ?? 0 }}</span>
        </a>

        <!-- Extensions -->
        <div class="sidebar-category-header">
            <span>Infraestrutura</span>
        </div>

        <a href="/admin/servers" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/servers')) ? 'active' : '' }}" title="Servidores">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
            <span class="sidebar-text whitespace-nowrap">Servidores</span>
            <span class="item-count">{{ $serversCount ?? 0 }}</span>
        </a>

        <a href="/admin/nodes" class="nav-link {{ (isset($request) && str_starts_with($request->getPath(), '/admin/nodes')) ? 'active' : '' }}" title="Nodes">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            <span class="sidebar-text whitespace-nowrap">Nodes</span>
            <span class="item-count">{{ $nodesCount ?? 0 }}</span>
        </a>
    </nav>

    <!-- User Profile Dropdown na Sidebar -->
    <div class="relative shrink-0 mt-auto border-t border-white/[0.02]" id="sidebar-user-container">

        <!-- Dropdown Menu do Perfil (Com a pegada Glassmorphism daqui vc não reclamou kkk) -->
        <div id="user-dropdown" class="absolute bottom-full left-0 p-4 mb-2 z-[9999] w-[260px] transition-all duration-300 opacity-0 pointer-events-none translate-y-4">
            <div class="p-[1px] rounded-[18px] bg-gradient-to-br from-white/10 via-transparent to-transparent shadow-main">
                <div class="bg-[#111113]/90 backdrop-blur-xl rounded-[17px] flex flex-col py-2 overflow-hidden">
                    <a href="/admin/profile" class="px-5 py-3 text-sm font-medium text-textLabel hover:text-textValue hover:bg-white/5 transition-colors flex items-center gap-3">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <span class="sidebar-text">Perfil</span>
                    </a>

                    <div class="h-[1px] w-full bg-white/[0.03] my-1"></div>

                    <a href="/" class="px-5 py-3 text-sm font-medium text-textLabel hover:text-textValue hover:bg-white/5 transition-colors flex items-center gap-3">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                        <span class="sidebar-text">Sair do Admin</span>
                    </a>
                    <form method="POST" class="m-0 p-0" id="signout">
                        <button type="submit" class="w-full text-left px-5 py-3 text-sm font-medium text-danger hover:bg-danger/10 transition-colors flex items-center gap-3">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="shrink-0"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                            <span class="sidebar-text">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Botão Toggle do Usuário -->
        <button id="user-dropdown-toggle" class="w-full flex items-center justify-between gap-3 px-6 py-5 hover:bg-white/[0.02] transition-colors cursor-pointer text-left focus:outline-none">
            <div class="flex items-center gap-3 overflow-hidden">
                <img src="https://www.gravatar.com/avatar/{{ md5(strtolower(trim($user->email ?? ''))) }}?s=80&d=mp" alt="Avatar" class="w-9 h-9 rounded-full shrink-0 shadow-sm border border-white/10">
                <div class="flex flex-col truncate sidebar-text">
                    <span class="text-[14px] font-bold text-textValue">{{ $user->first_name ?? 'Admin' }}</span>
                    <span class="text-[11px] text-textSub font-medium truncate">{{ $user->email ?? 'admin@hight.cloud' }}</span>
                </div>
            </div>
            <svg id="user-dropdown-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" class="text-textSub sidebar-text shrink-0 transition-transform duration-300"><path d="m18 15-6-6-6 6"/></svg>
        </button>
    </div>
</aside>

<div class="admin-content flex flex-col relative min-h-screen">

    <main class="p-8 md:p-12 flex-1 relative z-10">
        <!-- Alertas de Feedback com Borda Fake -->
        @if(isset($success))
        <div class="mb-8 p-[1px] rounded-xl bg-gradient-to-r from-success/40 to-transparent shadow-main animate-[fadeSlideIn_0.4s_ease-out]">
            <div class="p-4 rounded-[11px] bg-[#111113] text-success text-[13px] font-bold flex items-center gap-3">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                {{ $success }}
            </div>
        </div>
        @endif
        @if(isset($error))
        <div class="mb-8 p-[1px] rounded-xl bg-gradient-to-r from-danger/40 to-transparent shadow-main animate-[fadeSlideIn_0.4s_ease-out]">
            <div class="p-4 rounded-[11px] bg-[#111113] text-danger text-[13px] font-bold flex items-center gap-3">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                {{ $error }}
            </div>
        </div>
        @endif

        <!-- ONDE ENTRA TODO O SEU CONTEÚDO ORIGINAL VIA BLADE YIELD -->
        @yield('content')
    </main>

    <!-- FOOTER -->
    <footer class="mt-auto py-6 px-12 text-left text-[12px] text-textSub bg-transparent relative z-10 border-t border-white/[0.02]">
        <p>&copy; {{ date('Y') }} <a href="https://hight.cloud" class="hover:text-primary transition-colors font-medium">Lunar Panel</a>. Todos os direitos reservados.</p>
    </footer>
</div>

<script notRepeat="true">
    document.addEventListener('DOMContentLoaded', () => {
        // --- 1. Lógica do Dropdown do Usuário ---
        const userToggle = document.getElementById('user-dropdown-toggle');
        const userDropdown = document.getElementById('user-dropdown');
        const userIcon = document.getElementById('user-dropdown-icon');

        if (userToggle && userDropdown) {
            userToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('opacity-0');
                userDropdown.classList.toggle('pointer-events-none');
                userDropdown.classList.toggle('translate-y-4');
                userIcon.classList.toggle('rotate-180');
            });

            document.addEventListener('click', (e) => {
                if (!userToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
                    userIcon.classList.remove('rotate-180');
                }
            });
        }

        // --- 2. Atalhos de Teclado ---
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }

        document.addEventListener('keydown', (e) => {
            // (Ctrl+B / Cmd+B) para recolher a Sidebar
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
                e.preventDefault();
                document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', document.body.classList.contains('sidebar-collapsed'));
            }

            // (Ctrl+F / Cmd+F) para focar na busca da sidebar
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'f') {
                e.preventDefault();
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    // Se a sidebar estiver fechada, abre ela primeiro pra mostrar o input
                    if (document.body.classList.contains('sidebar-collapsed')) {
                        document.body.classList.remove('sidebar-collapsed');
                        localStorage.setItem('sidebar-collapsed', 'false');
                        // Pequeno delay pra dar tempo da sidebar abrir
                        setTimeout(() => searchInput.focus(), 300);
                    } else {
                        searchInput.focus();
                    }
                }
            }
        });

        // --- 3. Signout ---
        const signoutForm = document.getElementById('signout');
        if (signoutForm) {
            signoutForm.addEventListener('submit', (e) => {
                e.preventDefault();
                fetch('/api/auth/signout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
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