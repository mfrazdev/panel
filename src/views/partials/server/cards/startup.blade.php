<div class="bg-cards border border-white/5 shadow-main rounded-xl overflow-hidden flex flex-col break-inside-avoid w-full mb-8 lg:col-span-2">
    <!-- STREAMING_CHUNK:Renderizando cabeçalho do comando de inicialização -->
    <div class="px-8 py-5 border-b border-white/5">
        <h3 class="text-[12px] font-black text-textValue uppercase tracking-[0.2em]">Comando de Startup</h3>
    </div>

    <div class="p-8 flex flex-col gap-8">
        <!-- STREAMING_CHUNK:Configurando input do comando customizado -->
        <div class="flex flex-col gap-3">
            <label for="server-startup-command" class="text-[11px] font-black text-textSub uppercase tracking-widest ml-1">
                Comando Customizado
            </label>
            <input
                    type="text"
                    name="startupCommand"
                    id="server-startup-command"
                    value="{{ isset($resource) ? ((is_array($resource) || $resource instanceof \ArrayAccess) ? ($resource['startupCommand'] ?? '') : ($resource->startupCommand ?? '')) : '' }}"
                    class="w-full bg-black/20 border border-white/5 rounded-lg px-5 py-4 text-[14px] text-textValue font-medium placeholder-textSub focus:ring-1 focus:ring-primary outline-none transition-all duration-300 shadow-inner"
                    placeholder="Ex.: java -Xms128M -jar server.jar"
            >
            <p class="text-[12px] font-medium text-textSub mt-1 ml-1 leading-relaxed">
                Personalize o comando de inicialização. Se deixado vazio, o sistema usará a base definida no Core.
            </p>
        </div>

        <!-- STREAMING_CHUNK:Sessão do comando original para referência -->
        <div id="original-startup-container" class="flex flex-col gap-3 pt-6 border-t border-white/5 hidden">
            <label class="text-[11px] font-black text-primary uppercase tracking-widest ml-1">
                Comando Original do Core
            </label>

            <div class="relative group">
                <input
                        type="text"
                        id="original-startup-command"
                        value=""
                        readonly
                        onclick="this.select()"
                        class="w-full bg-black/40 border border-white/5 rounded-lg px-5 py-4 text-[13px] text-textValue font-mono focus:outline-none shadow-inner cursor-text group-hover:border-primary/30 transition-all"
                        title="Clique para selecionar e copiar"
                >
                <!-- Ícone de ajuda/cópia discreto -->
                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-textSub group-hover:text-primary transition-colors">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                </div>
            </div>

            <p class="text-[11px] font-medium text-textSub mt-1 ml-1">
                Este é o comando padrão. Útil para referência caso precise restaurar configurações.
            </p>
        </div>
    </div>
</div>