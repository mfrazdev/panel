<!-- Removido o overflow-hidden e adicionado relative z-20 -->
<div class="bg-cards border border-white/5 shadow-main rounded-xl flex flex-col break-inside-avoid w-full mb-8 lg:col-span-2 relative z-20">
    <div class="px-8 py-5 border-b border-white/5">
        <h3 class="text-[12px] font-black text-textValue uppercase tracking-[0.2em]">Variáveis de Ambiente</h3>
    </div>

    <div class="p-8">
        <div class="mb-6">
            <div class="text-[14px] text-textValue font-bold">
                {{ isset($resource) ? 'Edite as variáveis do core selecionado' : 'Configure as variáveis do core selecionado' }}
            </div>
            <div class="text-[12px] text-textSub mt-1 leading-relaxed">
                As variáveis aparecem em grid e o valor padrão definido pelo core é aplicado automaticamente.
            </div>
        </div>

        <!-- Grid de variáveis dinâmico -->
        <div id="server-env-container" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="text-[13px] text-textSub md:col-span-2 p-5 bg-black/20 border border-white/5 rounded-lg shadow-inner italic">
                Selecione um core para carregar as variáveis...
            </div>
        </div>
    </div>
</div>

<script>
    (function(){
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
            const ownerHidden = document.getElementById('server-owner-id');
            const ownerSearch = document.getElementById('server-owner-search');
            const ownerDropdown = document.getElementById('server-owner-dropdown');
            const nodeHidden = document.getElementById('server-node-id');
            const nodeList = document.getElementById('server-node-list');
            const coreHidden = document.getElementById('server-core-id');
            const coreList = document.getElementById('server-core-list');
            const dockerHidden = document.getElementById('server-docker-image');
            const dockerSelect = document.getElementById('server-docker-select');
            const dockerCustom = document.getElementById('server-docker-custom');

            const startupInput = document.getElementById('server-startup-command');
            const originalStartupInput = document.getElementById('original-startup-command');
            const originalStartupContainer = document.getElementById('original-startup-container');

            const envContainer = document.getElementById('server-env-container');

            if (!envContainer) return;


            // -------------------- Core & Env Logic --------------------
            const envSaved = (() => {
                try {
                    const raw = {!! json_encode(isset($resource) ? ((is_array($resource) || $resource instanceof \ArrayAccess) ? ($resource['envVars'] ?? '') : ($resource->envVars ?? '')) : '') !!};
                    return raw ? JSON.parse(raw) : {};
                } catch (e) { return {}; }
            })();

            const renderEnvVars = (vars) => {
                envContainer.innerHTML = '';
                if (!vars.length) {
                    envContainer.innerHTML = '<div class="text-[13px] text-textSub md:col-span-2 p-5 bg-black/20 border border-white/5 rounded-lg shadow-inner italic">Sem variáveis definidas neste core.</div>';
                    return;
                }

                // Adicionado o 'index' para o cálculo do z-index dinâmico
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
                    // Adicionado 'relative' e lógica de z-index decrescente para não encavalar
                    card.className = 'bg-black/20 border border-white/5 rounded-lg p-5 shadow-inner flex flex-col gap-3 relative';
                    card.style.zIndex = 50 - index;

                    card.innerHTML = `
                        <div>
                            <div class="text-textValue font-black text-[11px] uppercase tracking-widest">${escapeHtml(v.name || key)} ${rules.required ? '<span class="text-primary">*</span>' : ''}</div>
                            <div class="text-[11px] font-medium text-textSub mt-1 leading-relaxed">${escapeHtml(v.description || '')}</div>
                        </div>
                        <div class="relative">${fieldHtml}</div>
                    `;
                    envContainer.appendChild(card);
                });

                // Inicializa os Custom Selects que acabaram de ser adicionados ao DOM
                if (typeof window.initCustomSelects === 'function') {
                    window.initCustomSelects();
                }
            };

            const renderCores = (cores) => {
                if (!coreList) return;
                coreList.innerHTML = '';
                cores.forEach(c => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.dataset.coreId = c.id;
                    btn.className = 'w-full text-left bg-black/20 border border-white/5 rounded-lg px-5 py-4 flex items-center justify-between hover:border-white/10 transition-all group';
                    btn.innerHTML = `<div><div class="text-[13px] text-textValue font-bold group-hover:text-primary transition-colors">${escapeHtml(c.name)}</div><div class="text-[11px] text-textSub mt-0.5">${escapeHtml(c.description || '')}</div></div><div class="text-[11px] text-textSub font-mono">#${c.id}</div>`;

                    btn.onclick = () => {
                        coreHidden.value = c.id;
                        coreList.querySelectorAll('[data-core-id]').forEach(x => {
                            x.classList.remove('border-primary', 'bg-primary/5');
                            x.classList.add('border-white/5', 'bg-black/20');
                        });
                        btn.classList.remove('border-white/5', 'bg-black/20');
                        btn.classList.add('border-primary', 'bg-primary/5');

                        // Atualiza imagens docker
                        if (dockerSelect) {
                            dockerSelect.innerHTML = '';
                            (c.dockerImages || []).forEach(img => {
                                const val = typeof img === 'string' ? img : (img.image || img.value);
                                const lab = typeof img === 'string' ? img : (img.name || img.label || val);
                                const opt = document.createElement('option');
                                opt.value = val; opt.textContent = lab;
                                dockerSelect.appendChild(opt);
                            });
                        }
                        // Sugere startup customizado (somente se o input customizado estiver vazio)
                        if (startupInput && !startupInput.value) {
                            startupInput.value = c.startupCommand || c.startup_command || '';
                        }

                        // Comando Original
                        const originalStartupInput = document.getElementById('original-startup-command');
                        const originalStartupContainer = document.getElementById('original-startup-container');

                        if (originalStartupInput && originalStartupContainer) {
                            originalStartupInput.value = c.startupCommand || c.startup_command || 'Nenhum comando padrão definido no Core.';
                            originalStartupContainer.classList.remove('hidden');
                        }

                        renderEnvVars(c.variables || []);
                    };
                    coreList.appendChild(btn);
                });

                if (coreHidden.value) {
                    const active = coreList.querySelector(`[data-core-id="${coreHidden.value}"]`);
                    if (active) active.click();
                }
            };

            fetch('/admin/api/cores/list').then(r => r.json()).then(renderCores);
        });
    })();
</script>