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
                    <!-- Fundo super sutil e borda translúcida ao invés do bg-cards chapado -->
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

        <!-- Seção de Atualização -->
        <!-- Devolvendo a borda no contêiner principal para delinear o card -->
        <div class="bg-cards border border-terciary shadow-main rounded-xl overflow-hidden flex flex-col w-full mb-8">
            <!-- Header sem cor de fundo diferente, apenas dividido pela borda -->
            <div class="px-6 py-4 border-b border-terciary flex items-center gap-3">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" class="text-primary" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                <h3 class="text-[13px] font-bold text-textValue uppercase tracking-wider">Status do Sistema</h3>
            </div>

            <div class="p-6 flex flex-col gap-7">
                @if(isset($has_update) && $has_update)
                    <!-- Alerta: Atualização Disponível -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 bg-white/5 border border-terciary rounded-xl p-6 shadow-sm relative overflow-hidden">
                        <!-- Efeito de brilho sutil no fundo -->
                        <div class="absolute inset-0 bg-primary/5 pointer-events-none"></div>

                        <div class="flex items-center gap-5 z-10">
                            <!-- Ícone interno também com borda sutil ao invés de sombra/fundo pesado -->
                            <div class="w-12 h-12 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shadow-sm shrink-0 animate-pulse">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                            </div>
                            <div class="flex flex-col gap-1">
                                <h4 class="text-[15px] font-bold text-textValue">Nova Atualização Disponível!</h4>
                                <p class="text-sm font-medium text-textSub">
                                    A versão <span class="text-primary font-bold">{{ $latest_version ?? 'v2.1.0' }}</span> está disponível. Você está utilizando a <span class="text-textValue">{{ $current_version ?? 'v2.0.0' }}</span>.
                                </p>
                            </div>
                        </div>

                        <div class="z-10 w-full sm:w-auto flex flex-col sm:flex-row gap-3">
                            <!-- Link do GitHub transparente com borda -->
                            <a href="{{ $github_url ?? 'https://github.com/mfrazlab/panel/releases/latest' }}" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto bg-transparent border border-terciary hover:bg-white/5 text-textValue px-6 py-2.5 rounded-lg text-[13px] font-bold transition-all duration-300 flex items-center justify-center gap-2">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
                                Release Notes
                            </a>
                            <!-- Botão de Atualizar -->
                            <button id="btn-update" class="w-full sm:w-auto bg-primary hover:brightness-110 text-[#09090b] px-6 py-2.5 rounded-lg text-[13px] font-bold shadow-[0_0_15px_rgba(45,212,191,0.2)] transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer">
                                Atualizar Painel
                            </button>
                        </div>
                    </div>
                @else
                    <!-- Alerta: Sistema Atualizado -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 bg-white/5 border border-terciary rounded-xl p-6 shadow-sm">
                        <div class="flex items-center gap-5">
                            <div class="w-12 h-12 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-success shadow-sm shrink-0">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            </div>
                            <div class="flex flex-col gap-1">
                                <h4 class="text-[15px] font-bold text-textValue">Sistema Atualizado</h4>
                                <p class="text-sm font-medium text-textSub">
                                    Seu <span class="text-textValue">Lunar Panel</span> está rodando a versão mais recente (<span class="text-textValue">{{ $current_version ?? 'v2.0.0' }}</span>). Tudo pronto por aqui.
                                </p>
                            </div>
                        </div>

                        <!-- Badge com borda e transparência leve -->
                        <div class="hidden sm:flex items-center gap-2 px-4 py-2 rounded-lg bg-white/5 border border-terciary shadow-sm text-textSub text-[11px] font-black uppercase tracking-widest">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Verificado
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
                        console.log(text)
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