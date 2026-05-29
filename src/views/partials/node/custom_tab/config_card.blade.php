<!-- Só exibe o card da configuração se o node já estiver criado (tiver ID) -->
@if(isset($resource) && isset($resource->id))
    <div class="bg-cards border border-white/5 shadow-main rounded-xl overflow-hidden flex flex-col mb-8 break-inside-avoid w-full">
        <!-- STREAMING_CHUNK:Renderizando cabeçalho do arquivo de configuração -->
        <div class="px-8 py-5 border-b border-white/5">
            <h3 class="text-[12px] font-black text-textValue uppercase tracking-[0.2em]">Arquivo config.yml</h3>
        </div>

        <!-- STREAMING_CHUNK:Configurando área do editor Monaco -->
        <div class="p-8 flex flex-col gap-7">
            <div class="flex flex-col gap-3 w-full">
                <label class="text-[11px] font-black text-textSub uppercase tracking-widest ml-1">Configuração do Daemon</label>

                <div class="relative group">
                    <!-- Container Monaco com estilo "Inset" (escavado) para profundidade -->
                    <div class="bg-black/20 border border-white/5 rounded-lg shadow-inner overflow-hidden">
                        <div id="monaco-config-editor" class="w-full h-64"></div>
                    </div>
                </div>

                <p class="text-[12px] font-medium text-textSub mt-1 ml-1 leading-relaxed">
                    Copie o conteúdo acima e cole no arquivo <span class="text-primary font-bold">/etc/plume/config.yml</span> no servidor onde o node está instalado para vinculá-lo a este painel.
                </p>
            </div>
        </div>
    </div>

    <!-- Scripts do Monaco Editor via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs/loader.min.js"></script>
    <script>
        /* STREAMING_CHUNK:Inicializando script do editor Monaco */
        document.addEventListener('DOMContentLoaded', function() {
            require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.44.0/min/vs' }});

            require(['vs/editor/editor.main'], function() {
                // Tema customizado seguindo estritamente as cores escuras do seu sistema
                monaco.editor.defineTheme('lunarPanelTheme', {
                    base: 'vs-dark',
                    inherit: true,
                    rules: [],
                    colors: {
                        'editor.background': '#09090b00', // Transparente para usar o bg do container
                        'editor.lineHighlightBackground': '#ffffff05',
                        'editorLineNumber.foreground': '#3f3f46',
                        'editorIndentGuide.background': '#27272a',
                        'editorIndentGuide.activeBackground': '#3f3f46',
                        'editor.selectionBackground': '#3b82f640'
                    }
                });

                const configContent = [
                    `app:
  id: "{{ $resource->id }}"
  port: {{ $resource->port }}
  path: "/var/lib/plume"

sftp:
  port: {{ $resource->sftp }}

ssl:
  enabled: {{ $resource->httpsConnection === 1 ? 'true' : 'false' }}
  certPath: "/etc/letsencrypt/live/{{ $resource->ip }}/fullchain.pem"
  keyPath: "/etc/letsencrypt/live/{{ $resource->ip }}/privkey.pem"

remote:
  url: "http://localhost:8000"
  token: "{{ $resource->token }}"`
                ].join('\n');

                const editor = monaco.editor.create(document.getElementById('monaco-config-editor'), {
                    value: configContent,
                    language: 'yml',
                    theme: 'lunarPanelTheme',
                    readOnly: true,
                    minimap: { enabled: false },
                    scrollBeyondLastLine: false,
                    automaticLayout: true,
                    padding: { top: 16, bottom: 16 },
                    fontSize: 14,
                    fontFamily: "'JetBrains Mono', 'Fira Code', 'Courier New', monospace",
                    renderLineHighlight: 'all',
                    matchBrackets: 'always',
                    hideCursorInOverviewRuler: true,
                    scrollbar: {
                        vertical: 'hidden',
                        horizontal: 'hidden'
                    }
                });
            });
        });
    </script>
@endif
