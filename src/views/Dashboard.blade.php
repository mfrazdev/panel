@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_category', 'Sistema')
@section('page_name', 'Dashboard')

@section('content')
    <div class="flex flex-col max-w-[1400px] mx-auto animate-[fadeIn_0.4s_ease-out]">
        <!-- Cabeçalho da Página -->
        <div class="flex justify-between items-end mb-10">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <div class="w-10 h-10 rounded-2xl bg-cards flex items-center justify-center text-primary shadow-main">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    </div>
                    <h1 class="text-4xl font-black tracking-tight text-textValue">Dashboard</h1>
                </div>
                <p class="text-textSub text-sm font-medium ml-14">
                    Uma rápida olhada no seu sistema.
                </p>
            </div>
        </div>

        <!-- Seção de Atualização -->
        <div class="bg-cards shadow-main rounded-md overflow-hidden flex flex-col w-full mb-8">
            <div class="px-8 py-6 bg-sidebar">
                <h3 class="text-[12px] font-black text-textValue uppercase tracking-[0.2em]">Status do Sistema</h3>
            </div>

            <div class="p-8 flex flex-col gap-7">
                @if(isset($has_update) && $has_update)
                    <!-- Alerta: Atualização Disponível -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 bg-sidebar rounded-md p-6 shadow-inner relative overflow-hidden">
                        <!-- Efeito de brilho sutil no fundo -->
                        <div class="absolute inset-0 bg-primary/5 pointer-events-none"></div>

                        <div class="flex items-center gap-5 z-10">
                            <div class="w-14 h-14 rounded-full bg-primary/20 flex items-center justify-center text-primary shadow-main shrink-0 animate-pulse">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                            </div>
                            <div class="flex flex-col gap-1">
                                <h4 class="text-lg font-black text-textValue">Nova Atualização Disponível!</h4>
                                <p class="text-sm font-medium text-textSub">
                                    A versão <span class="text-primary font-bold">{{ $latest_version ?? 'v2.1.0' }}</span> está disponível no GitHub. Você está utilizando a <span class="text-textValue">{{ $current_version ?? 'v2.0.0' }}</span>.
                                </p>
                            </div>
                        </div>

                        <div class="z-10 w-full sm:w-auto flex flex-col sm:flex-row gap-3">
                            <!-- Botão de Atualizar -->
                            <button id="btn-update" class="w-full sm:w-auto bg-primary hover:brightness-110 text-textValue px-9 py-3.5 rounded-2xl text-sm font-bold shadow-main transition-all duration-300 flex items-center justify-center gap-3 transform hover:-translate-y-1 cursor-pointer">
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Atualizar Agora
                            </button>

                            <!-- Link direto para o GitHub -->
                            <a href="{{ $github_url ?? 'https://github.com/mfrazlab/panel/releases/latest' }}" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto bg-cards hover:brightness-110 text-textValue px-9 py-3.5 rounded-2xl text-sm font-bold shadow-main transition-all duration-300 flex items-center justify-center gap-3 transform hover:-translate-y-1">
                                <!-- Ícone do GitHub -->
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
                                GitHub
                            </a>
                        </div>
                    </div>
                @else
                    <!-- Alerta: Sistema Atualizado -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 bg-sidebar rounded-md p-6 shadow-inner">
                        <div class="flex items-center gap-5">
                            <div class="w-14 h-14 rounded-full bg-cards flex items-center justify-center text-textSub shadow-main shrink-0">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            </div>
                            <div class="flex flex-col gap-1">
                                <h4 class="text-lg font-black text-textValue">Sistema Atualizado</h4>
                                <p class="text-sm font-medium text-textSub">
                                    Você está rodando a versão mais recente (<span class="text-textValue">{{ $current_version ?? 'v2.0.0' }}</span>). Tudo pronto por aqui.
                                </p>
                            </div>
                        </div>

                        <!-- Tag indicativa (já que a verificação é no load) -->
                        <div class="hidden sm:flex items-center gap-2 px-4 py-2 rounded-xl bg-cards shadow-main text-textSub text-[11px] font-black uppercase tracking-widest">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Verificado ao acessar
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnUpdate = document.getElementById('btn-update');

        if (btnUpdate) {
            btnUpdate.addEventListener('click', async () => {
                // Salva o conteúdo original do botão e coloca um loading
                const originalContent = btnUpdate.innerHTML;
                btnUpdate.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Atualizando...
                `;
                btnUpdate.disabled = true;
                btnUpdate.classList.add('opacity-70', 'cursor-not-allowed', 'pointer-events-none');

                try {
                    // ATENÇÃO: Troque '/admin/update' pela rota exata que você registrou no Vatts.js
                    const response = await fetch('/admin/update', {
                        method: 'POST', // ou GET dependendo de como você definiu a rota no Router
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert('Erro: ' + data.message);
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