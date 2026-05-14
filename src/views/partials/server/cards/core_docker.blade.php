<!-- Removido o overflow-hidden e adicionado relative z-30 para sobrepor os cards de baixo -->
<div class="bg-cards border border-white/5 shadow-main rounded-xl flex flex-col break-inside-avoid w-full mb-8 lg:col-span-2 relative z-30">
    <div class="px-8 py-5 border-b border-white/5">
        <h3 class="text-[12px] font-black text-textValue uppercase tracking-[0.2em]">Core & Docker</h3>
    </div>

    <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-10">

        <!-- LADO ESQUERDO: Seleção de Core (z-20 para sobrepor o lado direito se precisar) -->
        <div class="flex flex-col gap-5 relative z-20">
            <div>
                <div class="text-[11px] font-black text-textSub uppercase tracking-widest ml-1">Selecionar Core</div>
                <div class="text-[12px] font-medium text-textSub ml-1 mt-1 leading-relaxed">
                    {{ isset($resource) ? 'Altere o core do servidor. O core dita as configurações base.' : 'O core define as variáveis e sugestões do servidor.' }}
                </div>
            </div>

            <!-- Select Customizado de Cores -->
            @php
                $currentCore = isset($resource) ? ((is_array($resource) || $resource instanceof \ArrayAccess) ? ($resource['coreId'] ?? '') : ($resource->coreId ?? '')) : '';
            @endphp

            @include('partials.components.custom-select', [
                'id' => 'server-core-select-component',
                'name' => 'coreId',
                'placeholder' => 'Carregando Cores...',
                'value' => $currentCore,
                'options' => []
            ])

        </div>

        <!-- LADO DIREITO: Docker -->
        <div class="flex flex-col gap-6 relative z-10">
            <div>
                <div class="text-[11px] font-black text-textSub uppercase tracking-widest ml-1">Imagem Docker</div>
                <div class="text-[12px] font-medium text-textSub ml-1 mt-1 leading-relaxed">
                    {{ isset($resource) ? 'Altere a imagem Docker ou escolha uma nova sugestão.' : 'Escolha uma sugestão do core ou informe uma imagem custom.' }}
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <!-- Select Customizado de Docker -->
                @php
                    $currentDocker = isset($resource) ? ((is_array($resource) || $resource instanceof \ArrayAccess) ? ($resource['dockerImage'] ?? '') : ($resource->dockerImage ?? '')) : '';
                @endphp

                @include('partials.components.custom-select', [
                    'id' => 'server-docker-select-component',
                    'name' => 'dockerImage',
                    'placeholder' => 'Selecione um Core primeiro...',
                    'value' => $currentDocker,
                    'options' => []
                ])

                <!-- Input Custom (Inset) -->
                <input
                        type="text"
                        id="server-docker-custom"
                        class="w-full bg-black/20 border border-white/5 rounded-lg px-5 py-4 text-[14px] text-textValue font-medium placeholder-textSub focus:ring-1 focus:ring-primary outline-none transition-all duration-300 shadow-inner hidden"
                        placeholder="Imagem personalizada (repo/image:tag)"
                >
            </div>

        </div>
    </div>
</div>

@if(isset($resource))
    <!-- Na edição, os cards de Inicialização e Variáveis de Ambiente vêm para dentro dessa aba unificada -->
    @include('partials.server.cards.startup')
    @include('partials.server.cards.env')
@endif

<script>
    (function(){
        // Variável global para armazenar a resposta da API dos Cores
        let globalCoresData = [];

        function debounce(fn, wait){
            let t;
            return function(...args){
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), wait);
            }
        }

        function escapeHtml(str){
            return (str ?? '').toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function parseRules(ruleString){
            const out = { required: false, def: null, regex: null, in: null };
            if (!ruleString) return out;
            const parts = String(ruleString).split('|').map(p => p.trim()).filter(Boolean);
            parts.forEach((p) => {
                if (p === 'required') out.required = true;
                else if (p.startsWith('default:')) out.def = p.substring('default:'.length);
                else if (p.startsWith('regex:')) out.regex = p.substring('regex:'.length);
                else if (p.startsWith('in:')) {
                    out.in = p.substring('in:'.length).split(',').map(s => s.trim());
                }
            });
            return out;
        }

        document.addEventListener('DOMContentLoaded', function(){
            const coreSelectComponent = document.getElementById('server-core-select-component');
            const dockerSelectComponent = document.getElementById('server-docker-select-component');
            const startupInput = document.getElementById('server-startup-command');
            const originalStartupInput = document.getElementById('original-startup-command');
            const originalStartupContainer = document.getElementById('original-startup-container');
            const envContainer = document.getElementById('server-env-container');

            // Pega o input escondido do Docker para re-injetar caso tenha edição salva
            const hiddenDockerInput = dockerSelectComponent ? dockerSelectComponent.querySelector('input[type="hidden"]') : null;
            const currentDockerSaved = hiddenDockerInput ? hiddenDockerInput.value : '';

            const envSaved = (() => {
                try {
                    const raw = {!! json_encode(isset($resource) ? ((is_array($resource) || $resource instanceof \ArrayAccess) ? ($resource['envVars'] ?? '') : ($resource->envVars ?? '')) : '') !!};
                    return raw ? JSON.parse(raw) : {};
                } catch (e) { return {}; }
            })();

            const renderEnvVars = (vars) => {
                if (!envContainer) return;
                envContainer.innerHTML = '';
                if (!vars.length) {
                    envContainer.innerHTML = '<div class="text-sm text-textSub md:col-span-2 p-4 bg-black/20 border border-white/5 rounded-lg shadow-inner">Sem variáveis definidas.</div>';
                    return;
                }

                vars.forEach((v, index) => {
                    const key = v.envVariable;
                    const rules = parseRules(v.rules || '');
                    let value = envSaved[key] ?? rules.def ?? '';

                    const fieldHtml = rules.in ? `
                        <div class="relative w-full custom-select-wrapper" id="env-select-${escapeHtml(key)}" data-value="${escapeHtml(value)}">
                            <input type="hidden" name="env[${escapeHtml(key)}]" id="env-select-${escapeHtml(key)}-input" value="${escapeHtml(value)}">
                            <button type="button" class="custom-select-button w-full bg-black/40 border border-white/5 rounded-lg px-4 py-3 text-[13px] font-medium flex justify-between items-center shadow-inner transition-all hover:border-white/10 focus:ring-1 focus:ring-primary outline-none">
                                <span class="custom-select-text truncate text-textValue">
                                    ${value ? escapeHtml(value) : 'Selecione uma opção...'}
                                </span>
                                <svg class="custom-select-icon text-textSub transition-transform duration-300" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"></path></svg>
                            </button>
                            <!-- dropdown mantido com z-50 -->
                            <div class="custom-select-dropdown absolute top-full left-0 w-full mt-2 bg-cards border border-white/5 shadow-main rounded-lg z-50 opacity-0 pointer-events-none transform -translate-y-2 transition-all duration-200 max-h-48 overflow-y-auto custom-scrollbar">
                                <div class="custom-select-options-container flex flex-col p-1">
                                    ${rules.in.map(opt => `
                                        <div class="custom-select-option px-4 py-3 rounded-md cursor-pointer hover:bg-white/5 text-[13px] text-textValue transition-colors flex items-center justify-between ${String(value) === String(opt) ? 'bg-primary/10 text-primary font-bold' : ''}" data-value="${escapeHtml(opt)}">
                                            <span class="truncate">${escapeHtml(opt)}</span>
                                            ${String(value) === String(opt) ? '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"></path></svg>' : ''}
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    ` : `
                        <input type="text" name="env[${escapeHtml(key)}]" value="${escapeHtml(value)}" class="w-full bg-black/40 border border-white/5 rounded-lg px-4 py-3 text-[13px] text-textValue focus:ring-1 focus:ring-primary outline-none shadow-inner">
                    `;

                    const card = document.createElement('div');
                    // Adicionado relative para o z-index funcionar e lógica decrescente
                    card.className = 'bg-black/20 border border-white/5 rounded-lg p-5 shadow-inner flex flex-col gap-3 relative';
                    card.style.zIndex = 50 - index;

                    card.innerHTML = `
                        <div>
                            <div class="text-textValue font-black text-[11px] uppercase tracking-widest">${escapeHtml(v.name || key)} ${rules.required ? '<span class="text-primary">*</span>' : ''}</div>
                            <div class="text-[12px] font-medium text-textSub mt-1 leading-relaxed">${escapeHtml(v.description || '')}</div>
                        </div>
                        <div class="relative">${fieldHtml}</div>
                    `;
                    envContainer.appendChild(card);
                });

                // Reinicializa os Custom Selects para aplicarem a animação e o evento de click nas opções injetadas
                if (typeof window.initCustomSelects === 'function') {
                    window.initCustomSelects();
                }
            };

            const applyCoreChanges = (coreId) => {
                const selectedCore = globalCoresData.find(c => String(c.id) === String(coreId));
                if (!selectedCore) return;

                // Atualiza imagens docker no component customizado
                if (dockerSelectComponent && typeof dockerSelectComponent.updateOptions === 'function') {
                    const dockerOptions = (selectedCore.dockerImages || []).map(img => {
                        const val = typeof img === 'string' ? img : (img.image || img.value);
                        const lab = typeof img === 'string' ? img : (img.name || img.label || val);
                        return { value: val, label: lab };
                    });

                    dockerSelectComponent.updateOptions(dockerOptions);

                    // Se o usuário está editando e salvou uma imagem que pertence a esse core, re-seleciona ela
                    if(currentDockerSaved && dockerOptions.some(opt => opt.value === currentDockerSaved)){
                        // Simula um click na opção (ou chama api de update se fizer)
                        const hiddenIn = dockerSelectComponent.querySelector('input[type="hidden"]');
                        if(hiddenIn){
                            hiddenIn.value = currentDockerSaved;
                            const txtSpan = dockerSelectComponent.querySelector('.custom-select-text');
                            const foundOpt = dockerOptions.find(opt => opt.value === currentDockerSaved);
                            if(txtSpan && foundOpt) txtSpan.textContent = foundOpt.label;
                        }
                    }
                }

                // Sugere startup customizado
                if (startupInput && !startupInput.value) {
                    startupInput.value = selectedCore.startupCommand || selectedCore.startup_command || '';
                }

                // Comando original
                if (originalStartupInput && originalStartupContainer) {
                    originalStartupInput.value = selectedCore.startupCommand || selectedCore.startup_command || 'Nenhum comando padrão definido no Core.';
                    originalStartupContainer.classList.remove('hidden');
                }

                renderEnvVars(selectedCore.variables || []);
            };

            // Escuta a mudança do Custom Select de Core
            if (coreSelectComponent) {
                const hiddenCoreInput = coreSelectComponent.querySelector('input[type="hidden"]');
                if (hiddenCoreInput) {
                    hiddenCoreInput.addEventListener('change', (e) => {
                        applyCoreChanges(e.target.value);
                    });
                }
            }

            // Fetch dos Cores na API
            fetch('/admin/api/cores/list')
                .then(r => r.json())
                .then(cores => {
                    globalCoresData = cores;

                    if (coreSelectComponent && typeof coreSelectComponent.updateOptions === 'function') {
                        const coreOptions = cores.map(c => ({
                            value: c.id,
                            label: `${c.name} (#${c.id})`
                        }));

                        coreSelectComponent.updateOptions(coreOptions);

                        // Atualiza o texto placeholder
                        const txt = coreSelectComponent.querySelector('.custom-select-text');
                        if(txt && txt.textContent.includes('Carregando')) {
                            txt.textContent = 'Selecione um Core...';
                        }

                        // Se já houver um core selecionado (ex: na edição), aplica as mudanças iniciais
                        const hiddenCoreInput = coreSelectComponent.querySelector('input[type="hidden"]');
                        if (hiddenCoreInput && hiddenCoreInput.value) {
                            applyCoreChanges(hiddenCoreInput.value);

                            // Força a atualização do label visual no select (caso o valor já exista mas as options não estavam montadas na DOM)
                            const foundCore = coreOptions.find(c => String(c.value) === String(hiddenCoreInput.value));
                            if(txt && foundCore) {
                                txt.textContent = foundCore.label;
                                txt.classList.remove('text-textSub');
                                txt.classList.add('text-textValue');
                            }
                        }
                    }
                });
        });
    })();
</script>