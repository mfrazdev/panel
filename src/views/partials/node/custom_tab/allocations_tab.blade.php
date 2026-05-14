<!-- ... existing code ... -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-8 items-start break-inside-avoid w-full">

    <!-- ESQUERDA: Lista de Alocações Existentes -->
    <div class="xl:col-span-2 bg-cards shadow-main rounded-md overflow-hidden flex flex-col w-full">
        <!-- Header com borda translúcida ao invés de cor divergente -->
        <div class="px-6 py-5 border-b border-white/5 flex items-center justify-between">
            <h3 class="text-[13px] font-black text-textValue uppercase tracking-wider flex items-center gap-2">
                Alocações Existentes
            </h3>
            <button type="submit" formaction="/admin/nodes/{{ $node->id }}/allocations/aliases" formmethod="POST" class="bg-black/20 border border-white/5 hover:bg-white/5 text-textSub hover:text-textValue px-4 py-2 rounded-md text-xs font-bold transition-all flex items-center gap-2 shadow-sm">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                Salvar Aliases
            </button>
        </div>

        <div class="p-0 overflow-x-auto w-full">
            <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                <!-- Cabeçalho da tabela seguindo a cor do card, com a divisória apenas em baixo -->
                <tr class="border-b border-white/5">
                    <th class="px-6 py-4 text-[11px] font-black text-textSub uppercase tracking-widest">Endereço IP</th>
                    <th class="px-4 py-4 text-[11px] font-black text-textSub uppercase tracking-widest w-1/3">IP Externo (Alias)</th>
                    <th class="px-4 py-4 text-[11px] font-black text-textSub uppercase tracking-widest">Porta</th>
                    <th class="px-4 py-4 text-[11px] font-black text-textSub uppercase tracking-widest">Atribuído A</th>
                    <th class="px-6 py-4 text-center text-[11px] font-black text-textSub uppercase tracking-widest">Ações</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                @forelse($allocations as $alloc)
                    <!-- Efeito hover ultra-leve -->
                    <tr class="hover:bg-white/[0.02] transition-colors group data-row">
                        <!-- IP -->
                        <td class="px-6 py-4">
                            <span class="text-textValue font-medium text-sm">{{ $alloc->ip }}</span>
                        </td>

                        <!-- ALIAS EDITÁVEL (com Inset Escuro) -->
                        <td class="px-4 py-4">
                            <input type="text" name="aliases[{{ $alloc->id }}]" value="{{ $alloc->externalIp }}" placeholder="Nenhum alias" class="w-full bg-black/20 border border-white/5 rounded-md px-4 py-2 text-sm text-textValue font-medium placeholder-textSub focus:ring-1 focus:ring-primary outline-none transition-all duration-300 shadow-inner">
                        </td>

                        <!-- PORTA (com Inset Escuro) -->
                        <td class="px-4 py-4">
                                <span class="bg-black/20 border border-white/5 px-3 py-1.5 rounded-md text-primary font-mono text-sm font-bold shadow-inner">
                                    {{ $alloc->port }}
                                </span>
                        </td>

                        <!-- ATRIBUÍDO A -->
                        <td class="px-4 py-4">
                            @if($alloc->assignedTo)
                                <a href="/admin/servers/{{ $alloc->assignedTo }}" class="text-[11px] font-bold text-info hover:brightness-110 cursor-pointer underline decoration-info/30 underline-offset-4">
                                    {{ $alloc->assignedTo }}
                                </a>
                            @else
                                <span class="text-[12px] font-medium text-textSub italic">
                                        Disponível
                                    </span>
                            @endif
                        </td>

                        <!-- LIXEIRA -->
                        <td class="px-6 py-4 flex justify-center">
                            @if(!$alloc->assignedTo)
                                <a href="/admin/nodes/{{ $node->id }}/allocations/{{ $alloc->id }}/delete" onclick="return confirm('Tem certeza que deseja remover a porta {{ $alloc->port }}?');" class="bg-transparent hover:bg-danger/10 text-danger p-2 rounded-md transition-all border border-transparent hover:border-danger/20">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <span class="text-textSub text-sm font-medium">Nenhuma alocação cadastrada neste node.</span>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- DIREITA: Formulário de Adicionar Novas -->
    <div class="xl:col-span-1 bg-cards shadow-main rounded-md overflow-hidden flex flex-col w-full ">
        <div class="px-6 py-5 border-b border-white/5">
            <h3 class="text-[13px] font-black text-textValue uppercase tracking-wider flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                Atribuir Novas Alocações
            </h3>
        </div>

        <div class="p-6 flex flex-col gap-6">

            <!-- Endereço IP (Inset) -->
            <div class="flex flex-col gap-2">
                <label class="text-[11px] font-black text-textSub uppercase tracking-widest ml-1">
                    Endereço IP <span class="text-primary ml-1 text-sm">*</span>
                </label>
                <input type="text" name="allocation_ip" placeholder="Ex: 192.168.1.1" value="{{ $node->ip }}" class="w-full bg-black/20 border border-white/5 rounded-md px-5 py-3.5 text-sm text-textValue font-medium placeholder-textSub focus:ring-1 focus:ring-primary outline-none transition-all duration-300 shadow-inner">
                <p class="text-[11px] font-medium text-textSub mt-1 ml-1 leading-relaxed">IP onde as portas serão atribuídas.</p>
            </div>

            <!-- IP Externo (Inset) -->
            <div class="flex flex-col gap-2">
                <label class="text-[11px] font-black text-textSub uppercase tracking-widest ml-1">IP Externo (Alias)</label>
                <input type="text" name="allocation_external_ip" placeholder="Ex: node01.host.com" class="w-full bg-black/20 border border-white/5 rounded-md px-5 py-3.5 text-sm text-textValue font-medium placeholder-textSub focus:ring-1 focus:ring-primary outline-none transition-all duration-300 shadow-inner">
                <p class="text-[11px] font-medium text-textSub mt-1 ml-1 leading-relaxed">Se quiser atribuir um alias padrão (FQDN), digite aqui.</p>
            </div>

            <!-- Portas (Inset) -->
            <div class="flex flex-col gap-2">
                <label class="text-[11px] font-black text-textSub uppercase tracking-widest ml-1">
                    Portas <span class="text-primary ml-1 text-sm">*</span>
                </label>
                <textarea name="allocation_ports" rows="2" placeholder="25565, 25566-25570" class="w-full bg-black/20 border border-white/5 rounded-md px-5 py-3.5 text-sm text-textValue font-medium placeholder-textSub focus:ring-1 focus:ring-primary outline-none transition-all duration-300 resize-none shadow-inner"></textarea>
                <p class="text-[11px] font-medium text-textSub mt-1 ml-1 leading-relaxed">
                    Insira portas individuais ou intervalos separados por vírgula (ex: 25565-25570, 25585).
                </p>
            </div>

            <hr class="border-white/5">

            <!-- Botão Adicionar (Texto Escuro no Primary) -->
            <button type="submit" formaction="/admin/nodes/{{ $node->id }}/allocations" formmethod="POST" class="w-full bg-primary hover:brightness-110 text-[#09090b] px-8 py-3.5 rounded-md text-[13px] font-bold shadow-[0_0_15px_rgba(45,212,191,0.2)] transition-all duration-300 flex items-center justify-center">
                Atribuir Portas
            </button>

        </div>
    </div>
</div>
<!-- ... existing code ... -->