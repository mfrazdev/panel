@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_category', 'Sistema')
@section('page_name', 'Dashboard')

@section('content')
    <div class="flex flex-col max-w-[1400px] mx-auto animate-[fadeIn_0.4s_ease-out]">
        <div class="flex justify-between items-end mb-10">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-primary shadow-sm">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    </div>
                    <h1 class="text-4xl font-black tracking-tight text-textValue">Dashboard</h1>
                </div>
                <p class="text-textSub text-sm font-medium ml-[56px]">
                    Bem-vindo ao Lunar Panel. Uma rápida olhada no seu sistema.
                </p>
            </div>
        </div>

        <div class="bg-cards border border-white/5 shadow-main rounded-xl overflow-hidden flex flex-col w-full mb-8">
            <div class="px-6 py-4 border-b border-white/5 flex items-center gap-3">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" class="text-primary" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                <h3 class="text-[13px] font-bold text-textValue uppercase tracking-wider">Status do Sistema</h3>
            </div>

            <div class="p-6 flex flex-col gap-7">
                @if(isset($has_update) && $has_update)
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 bg-white/5 border border-white/10 rounded-xl p-6 shadow-sm relative overflow-hidden">
                        <div class="absolute inset-0 bg-primary/5 pointer-events-none"></div>

                        <div class="flex items-center gap-5 z-10">
                            <div class="w-12 h-12 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shadow-sm shrink-0 animate-pulse">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                            </div>
                            <div class="flex flex-col gap-1">
                                <h4 class="text-[15px] font-bold text-textValue">Nova Atualização Disponível!</h4>
                                <p class="text-[13px] font-medium text-textSub">
                                    A versão <span class="text-primary font-bold">{{ $latest_version ?? 'v2.1.0' }}</span> está disponível. Você está utilizando a <span class="text-textValue">{{ $current_version ?? 'v2.0.0' }}</span>.
                                </p>
                            </div>
                        </div>

                        <div class="z-10 w-full sm:w-auto flex flex-col sm:flex-row gap-3">
                            <a href="{{ $github_url ?? 'https://github.com/mfrazdev/panel/releases/latest' }}" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto bg-transparent border border-white/10 hover:bg-white/5 text-textValue px-6 py-2.5 rounded-lg text-[13px] font-bold transition-all duration-300 flex items-center justify-center gap-2">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
                                Release Notes
                            </a>
                            <button id="btn-update" class="w-full sm:w-auto bg-primary hover:brightness-110 text-[#09090b] px-6 py-2.5 rounded-lg text-[13px] font-bold shadow-[0_0_15px_rgba(45,212,191,0.2)] transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer">
                                Atualizar Painel
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 bg-white/5 border border-white/5 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-5">
                            <div class="w-12 h-12 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-success shadow-sm shrink-0">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            </div>
                            <div class="flex flex-col gap-1">
                                <h4 class="text-[15px] font-bold text-textValue">Sistema Atualizado</h4>
                                <p class="text-[13px] font-medium text-textSub">
                                    Seu <span class="text-textValue">Lunar Panel</span> está rodando a versão mais recente (<span class="text-textValue">{{ $current_version ?? 'v2.0.0' }}</span>). Tudo pronto por aqui.
                                </p>
                            </div>
                        </div>
                        <div class="hidden sm:flex items-center gap-2 px-4 py-2 rounded-lg bg-white/5 border border-white/10 shadow-sm text-textSub text-[11px] font-black uppercase tracking-widest">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Verificado
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

            <!-- Desenvolvedores & Repositórios -->
            <div class="bg-cards border border-white/5 shadow-main rounded-xl overflow-hidden flex flex-col w-full h-full">
                <div class="px-6 py-4 border-b border-white/5 flex items-center gap-3">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" class="text-textLabel" viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                    <h3 class="text-[13px] font-bold text-textValue uppercase tracking-wider">Recursos para Desenvolvedores</h3>
                </div>
                <div class="p-6 flex flex-col gap-3 flex-1">

                    <!-- Link GitHub Panel -->
                    <a href="https://github.com/mfrazdev/panel" target="_blank" class="flex items-center justify-between p-4 bg-black/20 border border-white/5 hover:border-white/10 rounded-xl transition-all duration-300 group shadow-inner">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center text-textValue group-hover:text-primary transition-colors">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[14px] font-bold text-textValue group-hover:text-primary transition-colors">Lunar Panel</span>
                                <span class="text-[12px] font-medium text-textSub">Repositório Oficial do Web Panel</span>
                            </div>
                        </div>
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" class="text-textSub group-hover:text-primary transition-colors transform group-hover:translate-x-1" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    </a>

                    <!-- Link GitHub Daemon -->
                    <a href="https://github.com/mfrazdev/plume" target="_blank" class="flex items-center justify-between p-4 bg-black/20 border border-white/5 hover:border-white/10 rounded-xl transition-all duration-300 group shadow-inner">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center text-textValue group-hover:text-primary transition-colors">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[14px] font-bold text-textValue group-hover:text-primary transition-colors">Lunar Plume (Node)</span>
                                <span class="text-[12px] font-medium text-textSub">Código fonte do agente dos servidores</span>
                            </div>
                        </div>
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" class="text-textSub group-hover:text-primary transition-colors transform group-hover:translate-x-1" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    </a>

                </div>
            </div>

            <!-- Comunidade e Suporte -->
            <div class="bg-cards border border-white/5 shadow-main rounded-xl overflow-hidden flex flex-col w-full h-full">
                <div class="px-6 py-4 border-b border-white/5 flex items-center gap-3">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" class="text-textLabel" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <h3 class="text-[13px] font-bold text-textValue uppercase tracking-wider">Precisa de Ajuda?</h3>
                </div>
                <div class="p-6 flex flex-col gap-6 flex-1 justify-between">
                    <div class="flex flex-col gap-2">
                        <p class="text-[13px] font-medium text-textSub leading-relaxed">
                            Confira a documentação primeiro! Lá você encontra guias de instalação, configuração de nodes, SSL, e solução para os problemas mais comuns.
                        </p>
                        <p class="text-[13px] font-medium text-textSub leading-relaxed">
                            Se você ainda precisa de ajuda ou quer interagir com a comunidade, junte-se ao nosso servidor no Discord.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 mt-2">
                        <!-- Docs Button -->
                        <a href="https://hight.cloud" target="_blank" class="w-full flex-1 bg-white/5 border border-white/10 hover:bg-white/10 text-textValue px-5 py-3 rounded-xl text-[13px] font-bold transition-all duration-300 flex items-center justify-center gap-2 shadow-sm">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                            Documentação
                        </a>

                        <!-- Discord Button -->
                        <a href="https://discord.gg/9KTuwFNmt8" target="_blank" class="w-full flex-1 bg-[#5865F2] hover:brightness-110 text-white px-5 py-3 rounded-xl text-[13px] font-bold transition-all duration-300 flex items-center justify-center gap-2 shadow-[0_0_15px_rgba(88,101,242,0.2)]">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189Z"/></svg>
                            Servidor Discord
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnUpdate = document.getElementById('btn-update');

        if (btnUpdate) {
            btnUpdate.addEventListener('click', async () => {
                const originalContent = btnUpdate.innerHTML;
                btnUpdate.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Atualizando...
                `;
                btnUpdate.disabled = true;
                btnUpdate.classList.add('opacity-70', 'cursor-not-allowed', 'pointer-events-none');

                try {
                    const response = await fetch('/admin/update', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    let data;
                    const text = await response.text()
                    try {
                        data = JSON.parse(text)
                    } catch (e) {
                        console.log(text, 'invalido json')
                    }

                    if (data && data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert('Erro: ' + (data ? data.message : 'Resposta inválida do servidor.'));
                        restaurarBotao();
                    }
                } catch (error) {
                    alert('Erro na requisição de atualização. Verifique o console.');
                    console.error('Update error:', error);
                    restaurarBotao();
                }

                function restaurarBotao() {
                    btnUpdate.innerHTML = originalContent;
                    btnUpdate.disabled = false;
                    btnUpdate.classList.remove('opacity-70', 'cursor-not-allowed', 'pointer-events-none');
                }
            });
        }
    });
</script>