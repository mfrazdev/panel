<div class="bg-cards border border-white/5 shadow-main rounded-xl overflow-hidden flex flex-col break-inside-avoid w-full mb-8 lg:col-span-2">
    <div class="px-8 py-5 border-b border-white/5">
        <h3 class="text-[12px] font-black text-textValue uppercase tracking-[0.2em]">Alocação do Sistema</h3>
    </div>

    <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-10">
        <!-- Campos Ocultos de Controle para o form.html -->
        <input type="hidden" name="ownerId" id="server-owner-id" value="{{ isset($resource) ? ((is_array($resource) || $resource instanceof \ArrayAccess) ? ($resource['ownerId'] ?? '') : ($resource->ownerId ?? '')) : '' }}">
        <input type="hidden" name="nodeId" id="server-node-id" value="{{ isset($resource) ? ((is_array($resource) || $resource instanceof \ArrayAccess) ? ($resource['nodeUuid'] ?? '') : ($resource->nodeUuid ?? '')) : (isset($resource) ? ((is_array($resource) || $resource instanceof \ArrayAccess) ? ($resource['nodeId'] ?? '') : ($resource->nodeId ?? '')) : '') }}">
        <input type="hidden" name="allocationId" id="server-allocation-id" value="{{ isset($resource) ? ((is_array($resource) || $resource instanceof \ArrayAccess) ? ($resource['allocationId'] ?? '') : ($resource->allocationId ?? '')) : '' }}">

        <div class="flex flex-col gap-4">
            <div>
                <div class="text-[11px] font-black text-textSub uppercase tracking-widest ml-1">Proprietário</div>
                <div class="text-[12px] font-medium text-textSub ml-1 mt-1 leading-relaxed">
                    {{ isset($resource) ? 'Altere o proprietário buscando por e-mail (opcional).' : 'Busque por e-mail e selecione o usuário dono deste servidor.' }}
                </div>
            </div>

            <div class="relative flex items-center group w-full">
                <!-- Avatar Dinâmico Gravatar -->
                @php
                    $initialOwnerEmail = isset($ownerUser) ? $ownerUser->email : (isset($resource) ? ((is_array($resource) || $resource instanceof \ArrayAccess) ? ($resource['ownerEmail'] ?? '') : ($resource->ownerEmail ?? '')) : '');
                    $gravatarHash = $initialOwnerEmail ? md5(strtolower(trim($initialOwnerEmail))) : '';
                    $avatarFallback = "https://www.gravatar.com/avatar/?d=mp&s=64";
                    $avatarInitial = $gravatarHash ? "https://www.gravatar.com/avatar/{$gravatarHash}?d=mp&s=64" : $avatarFallback;
                @endphp

                <img id="server-owner-avatar" src="{{ $avatarInitial }}" alt="Avatar" class="absolute left-4 w-7 h-7 rounded-full shadow-main z-10 pointer-events-none transition-all duration-300 border border-white/10">

                <input
                        type="text"
                        id="server-owner-search"
                        class="w-full bg-black/20 border border-white/5 rounded-lg pl-14 pr-4 py-4 text-[14px] text-textValue font-medium placeholder-textSub focus:ring-1 focus:ring-primary outline-none transition-all duration-300 shadow-inner group-focus-within:border-white/10"
                        placeholder="Digite o e-mail do usuário..."
                        autocomplete="off"
                        value="{{ $initialOwnerEmail }}"
                >

                <!-- Dropdown de Sugestões de Busca -->
                <div id="server-owner-dropdown" class="absolute left-0 right-0 top-full mt-2 bg-cards border border-white/5 rounded-lg shadow-main overflow-hidden hidden z-50 flex-col max-h-60 overflow-y-auto custom-scrollbar opacity-0 pointer-events-none transform -translate-y-2 transition-all duration-200">
                    <div class="p-4 text-[13px] text-textSub italic">Digite para buscar...</div>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-8">

            <!-- LISTA DE NODES (Apenas Criação) usando Custom Select -->
            @if(!isset($resource))
                <div class="flex flex-col gap-3">
                    <div>
                        <div class="text-[11px] font-black text-textSub uppercase tracking-widest ml-1">Selecionar Node</div>
                        <div class="text-[12px] font-medium text-textSub ml-1 mt-1 leading-relaxed">
                            Escolha o servidor físico (Node) onde a instância será criada.
                        </div>
                    </div>

                    @include('partials.components.custom-select', [
                        'id' => 'server-node-select-component',
                        'name' => 'nodeIdSelectComponent',
                        'placeholder' => 'Carregando infraestrutura...',
                        'value' => '',
                        'options' => []
                    ])
                </div>
            @endif

            <div class="flex flex-col gap-3" id="server-allocation-container">
                <div>
                    <div class="text-[11px] font-black text-textSub uppercase tracking-widest ml-1">Porta de Alocação</div>
                    <div class="text-[12px] font-medium text-textSub ml-1 mt-1 leading-relaxed">
                        {{ isset($resource) ? 'Alterar porta (somente no mesmo node).' : 'Selecione uma porta disponível no node.' }}
                    </div>
                </div>

                @php
                    $currentPortId = isset($resource) ? ((is_array($resource) || $resource instanceof \ArrayAccess) ? ($resource['allocationId'] ?? '') : ($resource->allocationId ?? '')) : '';
                @endphp

                @include('partials.components.custom-select', [
                    'id' => 'server-allocation-select-component',
                    'name' => 'allocationIdSelectComponent',
                    'placeholder' => isset($resource) ? 'Carregando portas...' : 'Selecione um Node primeiro...',
                    'value' => $currentPortId,
                    'options' => []
                ])
            </div>

            <div class="flex flex-col gap-4" id="server-additional-fixed-container">
                <div>
                    <div class="text-[11px] font-black text-textSub uppercase tracking-widest ml-1">Portas Secundárias (FIXED)</div>
                    <div class="text-[12px] font-medium text-textSub ml-1 mt-1 leading-relaxed">
                        Adicione portas extras que este servidor poderá usar (ex: mapas web, plugins).
                    </div>
                </div>

                <div class="bg-black/20 border border-white/5 rounded-lg p-5 flex flex-col gap-4 shadow-inner">

                    <!-- Busca inline para adicionar novas portas -->
                    <div class="relative w-full group">
                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-textSub pointer-events-none transition-colors group-focus-within:text-primary">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></svg>
                        </div>
                        <input
                                type="text"
                                id="server-additional-fixed-search"
                                class="w-full bg-black/40 border border-white/5 rounded-lg pl-9 pr-4 py-3 text-[13px] text-textValue font-medium placeholder-textSub focus:ring-1 focus:ring-primary outline-none transition-all duration-300 group-focus-within:border-white/10"
                                placeholder="Pesquisar e vincular novas portas..."
                                autocomplete="off"
                        >
                        <!-- Painel flutuante de sugestões -->
                        <div id="server-additional-fixed-options" class="absolute left-0 right-0 bottom-full mb-2 bg-cards border border-white/5 rounded-lg shadow-main overflow-hidden hidden z-50 flex-col max-h-48 overflow-y-auto custom-scrollbar p-1">
                            <!-- Injetado por JS -->
                        </div>
                    </div>

                    <!-- Divisor -->
                    <div class="w-full h-[1px] bg-white/5"></div>

                    <!-- Lista de Fixeds em Pills flexíveis -->
                    <div id="server-additional-fixed-list" class="flex flex-wrap gap-2 max-h-40 overflow-y-auto custom-scrollbar content-start">
                        <div class="text-[13px] text-textSub italic py-1 px-1">Nenhuma porta extra vinculada.</div>
                    </div>
                </div>

                <input type="hidden" name="additionalAllocationsFixed" id="server-additional-fixed" value="[]">
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const debounce = (fn, wait) => {
            let t;
            return function(...args){
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), wait);
            }
        };

        const escapeHtml = (str) => {
            return (str ?? '').toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        };

        const serverId = "{{ isset($resource) ? ((is_array($resource) || $resource instanceof \ArrayAccess) ? ($resource['id'] ?? '') : ($resource->id ?? '')) : '' }}";

        // Elementos do Proprietário
        const ownerSearch = document.getElementById('server-owner-search');
        const ownerIdInput = document.getElementById('server-owner-id');
        const ownerDropdown = document.getElementById('server-owner-dropdown');
        const ownerAvatar = document.getElementById('server-owner-avatar');

        // Elementos de Select Customizado
        const nodeIdInput = document.getElementById('server-node-id');
        const nodeSelectComponent = document.getElementById('server-node-select-component');
        const allocationIdInput = document.getElementById('server-allocation-id');
        const allocationSelectComponent = document.getElementById('server-allocation-select-component');

        // Elementos das Portas Secundárias
        const additionalFixedField = document.getElementById('server-additional-fixed');
        const additionalFixedList = document.getElementById('server-additional-fixed-list');
        const additionalFixedSearch = document.getElementById('server-additional-fixed-search');
        const additionalFixedOptions = document.getElementById('server-additional-fixed-options');

        let fixedOptions = [];
        let fixedSearchTerm = '';

        const initialFixed = @json((function () {
            $raw = isset($resource) ? ($resource->additionalAllocations ?? '') : '';
            $data = json_decode($raw, true) ?: [];
            return $data['FIXED'] ?? [];
        })());

        let fixedIds = Array.isArray(initialFixed) ? initialFixed.map((v) => parseInt(v, 10)).filter(Boolean) : [];

        // Lógica Avançada de Gravatar com SHA-256 nativo (Super Rápido e Seguro)
        const getGravatarUrl = async (email) => {
            if (!email) return 'https://www.gravatar.com/avatar/?d=mp&s=64';
            const msgUint8 = new TextEncoder().encode(email.trim().toLowerCase());
            const hashBuffer = await crypto.subtle.digest('SHA-256', msgUint8);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
            return `https://www.gravatar.com/avatar/${hashHex}?d=mp&s=64`;
        };

        const updateOwnerAvatar = async (email) => {
            if(!ownerAvatar) return;
            ownerAvatar.src = await getGravatarUrl(email);
        };

        if (ownerSearch && ownerDropdown) {

            // Estado e função de Trava (Lock) do input de Proprietário
            let isOwnerLocked = !!ownerIdInput.value && !!ownerSearch.value;

            const applyOwnerLockState = () => {
                if (isOwnerLocked) {
                    ownerSearch.readOnly = true;
                    // Remove o estilo normal
                    ownerSearch.classList.remove('bg-black/20', 'border-white/5', 'text-textValue');
                    // Aplica estilo travado seguro (bg-black/40 garante fundo escuro, text-primary e border-primary dão o foco)
                    ownerSearch.classList.add('bg-black/40', 'border-primary', 'text-primary', 'cursor-pointer', 'shadow-md');
                    ownerSearch.title = "Clique para remover e selecionar outro usuário";
                } else {
                    ownerSearch.readOnly = false;
                    // Remove o estilo travado
                    ownerSearch.classList.remove('bg-black/40', 'border-primary', 'text-primary', 'cursor-pointer', 'shadow-md');
                    // Volta o estilo normal
                    ownerSearch.classList.add('bg-black/20', 'border-white/5', 'text-textValue');
                    ownerSearch.title = "";
                }
            };

            // Aplica estado inicial na montagem (para caso de edições onde o usuário já vem preenchido)
            applyOwnerLockState();

            // Escuta o clique no input para DESTRAVAR caso já exista um selecionado
            ownerSearch.addEventListener('click', () => {
                if (isOwnerLocked) {
                    isOwnerLocked = false;
                    ownerIdInput.value = '';
                    ownerSearch.value = '';
                    updateOwnerAvatar('');
                    applyOwnerLockState();
                    ownerSearch.focus(); // Traz o foco de volta pro usuário digitar
                }
            });

            const renderOwnerDropdown = (users) => {
                ownerDropdown.innerHTML = '';
                if (!users.length) {
                    ownerDropdown.innerHTML = '<div class="p-4 text-[13px] text-textSub italic">Nenhum usuário encontrado.</div>';
                } else {
                    users.forEach(u => {
                        const row = document.createElement('div');
                        row.className = 'w-full text-left px-4 py-3 hover:bg-white/5 transition-colors flex items-center gap-3 cursor-pointer rounded-md';

                        // Cria Imagem com Placeholder enquanto carrega o Gravatar
                        const img = document.createElement('img');
                        img.src = 'https://www.gravatar.com/avatar/?d=mp&s=64';
                        img.className = 'w-8 h-8 rounded-full shadow-sm shrink-0 border border-white/10';
                        getGravatarUrl(u.email).then(url => img.src = url);

                        const textContainer = document.createElement('div');
                        textContainer.className = 'flex flex-col truncate';
                        textContainer.innerHTML = `
                            <span class="text-[13px] font-bold text-textValue truncate">${escapeHtml(u.email)}</span>
                            <span class="text-[11px] text-textSub truncate">${escapeHtml(u.name || '')}</span>
                        `;

                        row.appendChild(img);
                        row.appendChild(textContainer);

                        row.addEventListener('click', () => {
                            ownerIdInput.value = u.uuid || u.id;
                            ownerSearch.value = u.email;
                            updateOwnerAvatar(u.email); // Atualiza o avatar fixo no input apenas quando CLICA

                            // Aplica a trava de seleção
                            isOwnerLocked = true;
                            applyOwnerLockState();

                            closeOwnerDropdown();
                        });
                        ownerDropdown.appendChild(row);
                    });
                }
                openOwnerDropdown();
            };

            const openOwnerDropdown = () => {
                // Previne de abrir o dropdown se o input estiver travado com uma seleção
                if (isOwnerLocked) return;

                ownerDropdown.classList.remove('hidden');
                setTimeout(() => {
                    ownerDropdown.classList.remove('opacity-0', 'pointer-events-none', '-translate-y-2');
                    ownerDropdown.classList.add('opacity-100', 'pointer-events-auto', 'translate-y-0');
                }, 10);
            };

            const closeOwnerDropdown = () => {
                ownerDropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
                ownerDropdown.classList.remove('opacity-100', 'pointer-events-auto', 'translate-y-0');
                setTimeout(() => ownerDropdown.classList.add('hidden'), 200);
            };

            const doOwnerSearch = debounce((term) => {
                // Impede busca se estiver travado
                if (isOwnerLocked) return;

                if (term.length < 2) {
                    closeOwnerDropdown();
                    return;
                }
                fetch(`/admin/api/users/search?email=${encodeURIComponent(term)}`)
                    .then(r => r.json()).then(renderOwnerDropdown);
            }, 300);

            ownerSearch.addEventListener('input', (e) => {
                // O evento input não deve ser disparado visualmente graças ao readOnly, mas por segurança testamos
                if (isOwnerLocked) return;

                const term = e.target.value.trim();
                doOwnerSearch(term);

                // Só reseta o avatar se o cara apagar tudo do campo de texto
                if (term === '') {
                    updateOwnerAvatar('');
                    ownerIdInput.value = '';
                }
            });

            document.addEventListener('click', (e) => {
                if(!ownerSearch.contains(e.target) && !ownerDropdown.contains(e.target)) {
                    closeOwnerDropdown();
                }
            });
        }

        const getFixedLabel = (item) => `${item.port}${item.externalIp ? ` (${item.externalIp})` : ''}`;

        const renderFixedList = () => {
            if (!additionalFixedList) return;

            if (!fixedIds.length) {
                additionalFixedList.innerHTML = '<div class="text-[13px] text-textSub italic py-1 px-1 w-full">Nenhuma porta extra vinculada.</div>';
                return;
            }

            additionalFixedList.innerHTML = '';
            fixedIds.forEach((id) => {
                const item = fixedOptions.find((opt) => String(opt.id) === String(id));
                // Design de "Pill" para a porta
                const pill = document.createElement('div');
                pill.className = 'flex items-center gap-2 px-3 py-1.5 bg-black/40 border border-white/5 rounded-lg group hover:border-danger/30 transition-colors cursor-default';
                pill.innerHTML = `
                    <span class="text-[12px] font-mono font-medium text-textValue">${item ? getFixedLabel(item) : `Porta #${id}`}</span>
                    <button type="button" data-fixed-remove="${id}" class="text-textSub hover:text-danger focus:outline-none transition-colors" title="Remover porta">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                    </button>
                `;
                additionalFixedList.appendChild(pill);
            });
        };

        const syncFixedField = () => {
            if (additionalFixedField) {
                additionalFixedField.value = JSON.stringify(fixedIds);
            }
        };

        const refreshFixedOptionsSelection = () => {
            if (!additionalFixedOptions) return;
            const term = fixedSearchTerm.toLowerCase();
            const selected = new Set(fixedIds.map((id) => String(id)));
            additionalFixedOptions.innerHTML = '';

            // Verifica se a API já foi chamada ou tem options
            if (!nodeIdInput.value) {
                additionalFixedOptions.innerHTML = '<div class="px-4 py-3 text-[13px] text-textSub italic">Selecione um Node primeiro.</div>';
                return;
            }

            const filtered = fixedOptions.filter((opt) => {
                const haystack = `${opt.label} ${opt.ip} ${opt.port} ${opt.externalIp || ''}`.toLowerCase();
                return haystack.includes(term) && !selected.has(String(opt.id));
            });

            if (!filtered.length) {
                // Mensagem amigável dependendo se procurou algo ou se acabou as portas
                const msg = term ? 'Nenhuma porta corresponde à busca.' : 'Não há mais portas livres neste Node.';
                additionalFixedOptions.innerHTML = `<div class="px-4 py-3 text-[13px] text-textSub italic">${msg}</div>`;
                return;
            }

            filtered.forEach((opt) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.fixedId = String(opt.id);
                button.className = `text-left w-full rounded-md px-4 py-2 hover:bg-white/5 transition-colors text-[13px] text-textValue font-mono flex items-center justify-between gap-2 group`;
                button.innerHTML = `
                    <span class="truncate">${opt.label}</span>
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" class="opacity-0 group-hover:opacity-100 text-primary transition-opacity"><path d="M12 5v14M5 12h14"></path></svg>
                `;
                additionalFixedOptions.appendChild(button);
            });
        };

        const applyFixedSelection = (id) => {
            const allocId = parseInt(String(id), 10);
            if (!allocId || fixedIds.includes(allocId)) return;
            fixedIds.push(allocId);
            renderFixedList();
            syncFixedField();
            additionalFixedSearch.value = '';
            fixedSearchTerm = '';
            additionalFixedOptions.classList.add('hidden');
        };

        const removeFixedSelection = (id) => {
            const allocId = parseInt(String(id), 10);
            fixedIds = fixedIds.filter((item) => item !== allocId);
            renderFixedList();
            syncFixedField();
            refreshFixedOptionsSelection(); // Atualiza a lista de opções pq uma porta voltou a ficar livre
        };

        if (additionalFixedSearch && additionalFixedOptions) {
            additionalFixedSearch.addEventListener('input', (e) => {
                fixedSearchTerm = e.target.value.trim() || '';
                refreshFixedOptionsSelection();
                additionalFixedOptions.classList.remove('hidden');
            });

            additionalFixedSearch.addEventListener('focus', () => {
                refreshFixedOptionsSelection();
                additionalFixedOptions.classList.remove('hidden');
            });

            document.addEventListener('click', (e) => {
                if(!additionalFixedSearch.contains(e.target) && !additionalFixedOptions.contains(e.target)) {
                    additionalFixedOptions.classList.add('hidden');
                }
            });

            additionalFixedOptions.addEventListener('click', (event) => {
                const target = event.target.closest('[data-fixed-id]');
                if (target) applyFixedSelection(target.dataset.fixedId);
            });
        }

        if (additionalFixedList) {
            additionalFixedList.addEventListener('click', (event) => {
                const target = event.target.closest('[data-fixed-remove]');
                if (target) removeFixedSelection(target.dataset.fixedRemove);
            });
        }

        const loadAllocations = (nodeId, forceRefreshSelect = true) => {
            if (!nodeId) return;

            if (forceRefreshSelect && allocationSelectComponent && typeof allocationSelectComponent.updateOptions === 'function') {
                allocationSelectComponent.updateOptions([]);
                const txtLabel = allocationSelectComponent.querySelector('.custom-select-text');
                if(txtLabel) txtLabel.textContent = "Buscando portas do Node...";
            }

            fetch(`/admin/api/allocations/list?nodeId=${encodeURIComponent(nodeId)}&serverId=${encodeURIComponent(serverId)}`)
                .then(res => res.json())
                .then((data) => {
                    if (forceRefreshSelect && allocationSelectComponent && typeof allocationSelectComponent.updateOptions === 'function') {
                        const txtLabel = allocationSelectComponent.querySelector('.custom-select-text');

                        if (!Array.isArray(data) || data.length === 0) {
                            allocationSelectComponent.updateOptions([]);
                            if(txtLabel) txtLabel.textContent = "Sem portas disponíveis neste Node";
                            return;
                        }

                        if(txtLabel) txtLabel.textContent = "Selecione a porta principal...";

                        const options = data.map(alloc => {
                            const isCurrent = (String(allocationIdInput.value) === String(alloc.id));
                            return {
                                value: alloc.id,
                                label: `${alloc.ip}:${alloc.port} ${alloc.externalIp ? `(${alloc.externalIp})` : ''} ${isCurrent ? '• Atual' : ''}`
                            };
                        });

                        allocationSelectComponent.updateOptions(options);

                        // Garante que o select exiba a opção correta na edição
                        if(allocationIdInput.value) {
                            const hiddenIn = allocationSelectComponent.querySelector('input[type="hidden"]');
                            if(hiddenIn){
                                hiddenIn.value = allocationIdInput.value;
                                const foundOpt = options.find(opt => String(opt.value) === String(allocationIdInput.value));
                                if(txtLabel && foundOpt) {
                                    txtLabel.textContent = foundOpt.label;
                                    txtLabel.classList.remove('text-textSub');
                                    txtLabel.classList.add('text-textValue');
                                }
                            }
                        }
                    }

                    // Separa as portas que não são a principal para a área de Fixed
                    const availableFixed = data.filter((alloc) => {
                        return String(alloc.id) !== String(allocationIdInput.value);
                    });

                    fixedOptions = availableFixed.map((alloc) => ({
                        id: String(alloc.id),
                        label: `${alloc.ip}:${alloc.port}${alloc.externalIp ? ` (${alloc.externalIp})` : ''}`,
                        ip: alloc.ip,
                        port: alloc.port,
                        externalIp: alloc.externalIp || null,
                    }));

                    // Coloca as alocações vinculadas nas Fixed (exceto a primária)
                    availableFixed.forEach(alloc => {
                        if (alloc.isAssignedToMe) {
                            const allocId = parseInt(alloc.id, 10);
                            if (allocId && !fixedIds.includes(allocId)) {
                                fixedIds.push(allocId);
                            }
                        }
                    });

                    renderFixedList();
                    refreshFixedOptionsSelection();
                });
        };

        // Escuta mudança de Node (via Custom Select)
        if (nodeSelectComponent) {
            const hiddenNodeInput = nodeSelectComponent.querySelector('input[type="hidden"]');
            if (hiddenNodeInput) {
                hiddenNodeInput.addEventListener('change', (e) => {
                    const selectedNodeId = e.target.value;
                    nodeIdInput.value = selectedNodeId;
                    allocationIdInput.value = ''; // Reset principal
                    fixedIds = []; // Limpa fixadas antigas
                    renderFixedList();
                    syncFixedField();

                    if(selectedNodeId) {
                        loadAllocations(selectedNodeId);
                    } else if(allocationSelectComponent) {
                        allocationSelectComponent.updateOptions([]);
                        const txtLabel = allocationSelectComponent.querySelector('.custom-select-text');
                        if(txtLabel) txtLabel.textContent = "Selecione um Node primeiro...";
                        fixedOptions = [];
                        refreshFixedOptionsSelection();
                    }
                });
            }

            fetch('/admin/api/nodes/list')
                .then(res => res.json())
                .then((data) => {
                    const onlineNodes = (data || []).filter(node => node.online);
                    const txtLabel = nodeSelectComponent.querySelector('.custom-select-text');

                    if (!onlineNodes || onlineNodes.length === 0) {
                        if(typeof nodeSelectComponent.updateOptions === 'function') nodeSelectComponent.updateOptions([]);
                        if(txtLabel) txtLabel.textContent = "Nenhum node online/disponível";
                        return;
                    }

                    if(txtLabel && txtLabel.textContent.includes('Carregando')) {
                        txtLabel.textContent = "Selecione um Node...";
                    }

                    const options = onlineNodes.map(node => ({
                        value: node.id,
                        label: `${node.name} (${node.location || 'GLOBAL'})`
                    }));

                    if(typeof nodeSelectComponent.updateOptions === 'function') {
                        nodeSelectComponent.updateOptions(options);
                    }
                });
        } else if (nodeIdInput.value) {
            loadAllocations(nodeIdInput.value);
        }

        // Escuta mudança de Alocação Principal (via Custom Select)
        if (allocationSelectComponent) {
            const hiddenAllocInput = allocationSelectComponent.querySelector('input[type="hidden"]');
            if(hiddenAllocInput) {
                hiddenAllocInput.addEventListener('change', (e) => {
                    const newVal = e.target.value;
                    allocationIdInput.value = newVal;

                    // Remove da lista secundaria caso ela vire a principal
                    const newPrimary = parseInt(newVal, 10);
                    if (newPrimary && fixedIds.includes(newPrimary)) {
                        fixedIds = fixedIds.filter((item) => item !== newPrimary);
                        renderFixedList();
                        syncFixedField();
                    }
                    loadAllocations(nodeIdInput.value, false);
                });
            }
        }

        renderFixedList();
        syncFixedField();
    });
</script>