@if(isset($resource) && isset($resource->id))
    <div class="bg-cards border border-white/5 shadow-main rounded-xl overflow-hidden flex flex-col mb-8 break-inside-avoid w-full">

        <!-- Header do Status sem fundo divergente -->
        <div class="px-8 py-5 border-b border-white/5 flex justify-between items-center">
            <h3 class="text-[12px] font-black text-textValue uppercase tracking-[0.2em]">Status em Tempo Real</h3>

            <div class="flex items-center gap-3">
                <div class="relative flex items-center justify-center">
                    <span class="absolute w-full h-full rounded-full bg-textSub opacity-20 node-ping-{{ $resource->id }}"></span>
                    <span class="relative w-2.5 h-2.5 rounded-full bg-textSub shadow-[0_0_8px_rgba(159,176,192,0.5)] node-indicator-{{ $resource->id }}"></span>
                </div>
                <span class="text-[11px] font-bold text-textSub uppercase tracking-widest node-text-{{ $resource->id }}">Conectando...</span>
            </div>
        </div>

        <div class="p-8 flex flex-col gap-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                <!-- Cards Internos ("Inset" profundo) -->
                <div class="bg-black/20 border border-white/5 p-5 rounded-lg shadow-inner flex flex-col gap-1">
                    <span class="text-[11px] font-black text-textSub uppercase tracking-widest">Uso de RAM</span>
                    <span class="text-xl font-mono font-medium text-textValue node-ram-{{ $resource->id }}">--</span>
                </div>

                <div class="bg-black/20 border border-white/5 p-5 rounded-lg shadow-inner flex flex-col gap-1">
                    <span class="text-[11px] font-black text-textSub uppercase tracking-widest">Carga de CPU</span>
                    <span class="text-xl font-mono font-medium text-textValue node-cpu-{{ $resource->id }}">--</span>
                </div>

                <div class="bg-black/20 border border-white/5 p-5 rounded-lg shadow-inner flex flex-col gap-1">
                    <span class="text-[11px] font-black text-textSub uppercase tracking-widest">Sistema Operacional</span>
                    <span class="text-sm font-semibold text-textValue mt-1 node-os-{{ $resource->id }}">--</span>
                </div>

                <div class="bg-black/20 border border-white/5 p-5 rounded-lg shadow-inner flex flex-col gap-1">
                    <span class="text-[11px] font-black text-textSub uppercase tracking-widest">Uptime</span>
                    <span class="text-sm font-semibold text-textValue mt-1 node-uptime-{{ $resource->id }}">--</span>
                </div>

                <div class="bg-black/20 border border-white/5 p-6 rounded-lg shadow-inner sm:col-span-2 mt-2">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                        <div class="flex flex-col gap-2">
                            <span class="text-[11px] font-black text-textSub uppercase tracking-widest">
                                Versão do Agent
                            </span>

                            <div class="flex items-center gap-4">
                                <span class="text-xl font-mono font-medium text-textValue node-version-{{ $resource->id }}">
                                    --
                                </span>

                                <!-- Badge de atualização seguindo o novo padrão do sistema -->
                                <span class="hidden px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-warning/10 text-warning border border-warning/20 node-update-badge-{{ $resource->id }}">
                                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-warning mr-1.5 shadow-[0_0_8px_var(--color-warning)]"></span>
                                    Atualização disponível
                                </span>
                            </div>
                        </div>

                        <!-- Info da Última Versão e Botão -->
                        <div class="hidden flex-col sm:flex-row items-start sm:items-center gap-5 node-latest-wrapper-{{ $resource->id }}">
                            <div class="sm:text-right flex flex-col gap-1">
                                <span class="text-[11px] font-black text-textSub uppercase tracking-widest">
                                    Última versão
                                </span>
                                <span class="text-sm font-mono font-bold text-primary node-latest-version-{{ $resource->id }}">
                                    --
                                </span>
                            </div>

                            <!-- Botão de Ação com Alto Contraste (Fundo claro, texto escuro) -->
                            <button type="button" onclick="updateNode('{{ $resource->id }}')"
                                    class="node-update-btn-{{ $resource->id }} w-full sm:w-auto bg-primary hover:brightness-110 text-[#09090b] px-6 py-2.5 rounded-lg text-[13px] font-bold shadow-[0_0_15px_rgba(45,212,191,0.2)] transition-all duration-300 flex items-center justify-center gap-2">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Atualizar Agora
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif