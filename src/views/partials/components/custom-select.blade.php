@php
    $id = $id ?? uniqid('select_');
    $name = $name ?? '';
    $placeholder = $placeholder ?? 'Selecione uma opção...';
    $value = $value ?? '';
    $options = $options ?? [];
@endphp

<div class="relative w-full custom-select-wrapper" id="{{ $id }}" data-value="{{ $value }}">
    <!-- Input oculto que realmente envia os dados para o form -->
    <input type="hidden" name="{{ $name }}" id="{{ $id }}-input" value="{{ $value }}">

    <!-- Botão principal do Select (Estilo Inset) -->
    <button type="button" class="custom-select-button w-full bg-black/20 border border-white/5 rounded-lg px-5 py-4 text-[14px] font-medium flex justify-between items-center shadow-inner transition-all hover:border-white/10 focus:ring-1 focus:ring-primary outline-none">
        <span class="custom-select-text truncate {{ empty($value) ? 'text-textSub' : 'text-textValue' }}">
            {{ $placeholder }}
        </span>
        <svg class="custom-select-icon text-textSub transition-transform duration-300" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"></path></svg>
    </button>

    <!-- Menu Dropdown -->
    <div class="custom-select-dropdown absolute top-full left-0 w-full mt-2 bg-cards border border-white/5 shadow-main rounded-lg z-50 opacity-0 pointer-events-none transform -translate-y-2 transition-all duration-200 max-h-64 overflow-y-auto custom-scrollbar">
        <!-- As opções podem vir do Blade ou ser injetadas via JS -->
        <div class="custom-select-options-container flex flex-col p-1">
            @forelse($options as $val => $label)
                <div class="custom-select-option px-4 py-3 rounded-md cursor-pointer hover:bg-white/5 text-[13px] text-textValue transition-colors flex items-center justify-between {{ (string)$val === (string)$value ? 'bg-primary/10 text-primary font-bold' : '' }}" data-value="{{ $val }}">
                    <span class="truncate">{{ $label }}</span>
                    @if((string)$val === (string)$value)
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"></path></svg>
                    @endif
                </div>
            @empty
                <!-- Placeholder caso as opções sejam carregadas via JS depois -->
                <div class="px-4 py-3 text-[13px] text-textSub italic empty-state">Nenhuma opção disponível.</div>
            @endforelse
        </div>
    </div>
</div>

<script>
    if (typeof window.initCustomSelects !== 'function') {
        window.initCustomSelects = function() {
            document.querySelectorAll('.custom-select-wrapper:not(.initialized)').forEach(wrapper => {
                wrapper.classList.add('initialized');
                const btn = wrapper.querySelector('.custom-select-button');
                const dropdown = wrapper.querySelector('.custom-select-dropdown');
                const icon = wrapper.querySelector('.custom-select-icon');
                const textSpan = wrapper.querySelector('.custom-select-text');
                const input = wrapper.querySelector('input[type="hidden"]');
                const optionsContainer = wrapper.querySelector('.custom-select-options-container');

                let isOpen = false;

                const toggleDropdown = () => {
                    isOpen = !isOpen;
                    if (isOpen) {
                        dropdown.classList.remove('opacity-0', 'pointer-events-none', '-translate-y-2');
                        icon.classList.add('rotate-180');
                        btn.classList.add('border-primary/50');
                    } else {
                        dropdown.classList.add('opacity-0', 'pointer-events-none', '-translate-y-2');
                        icon.classList.remove('rotate-180');
                        btn.classList.remove('border-primary/50');
                    }
                };

                const selectOption = (val, label, triggerEvent = true) => {
                    input.value = val;
                    textSpan.textContent = label;
                    textSpan.classList.remove('text-textSub');
                    textSpan.classList.add('text-textValue');

                    // Atualiza visual das opções
                    optionsContainer.querySelectorAll('.custom-select-option').forEach(opt => {
                        if (opt.dataset.value === val) {
                            opt.classList.add('bg-primary/10', 'text-primary', 'font-bold');
                            if (!opt.querySelector('svg')) {
                                opt.insertAdjacentHTML('beforeend', '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"></path></svg>');
                            }
                        } else {
                            opt.classList.remove('bg-primary/10', 'text-primary', 'font-bold');
                            const svg = opt.querySelector('svg');
                            if (svg) svg.remove();
                        }
                    });

                    if (isOpen) toggleDropdown();

                    // Dispara evento change para o form/JS existente saber que mudou
                    if (triggerEvent) {
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                };

                // Evento de abrir/fechar
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    // Fecha outros abertos
                    document.querySelectorAll('.custom-select-wrapper.initialized').forEach(other => {
                        if (other !== wrapper && !other.querySelector('.custom-select-dropdown').classList.contains('opacity-0')) {
                            other.querySelector('.custom-select-button').click();
                        }
                    });
                    toggleDropdown();
                });

                // Delegação de eventos para as opções (útil se as opções forem injetadas via JS depois)
                optionsContainer.addEventListener('click', (e) => {
                    const optionEl = e.target.closest('.custom-select-option');
                    if (optionEl) {
                        selectOption(optionEl.dataset.value, optionEl.querySelector('span').textContent.trim());
                    }
                });

                // Fechar ao clicar fora
                document.addEventListener('click', (e) => {
                    if (isOpen && !wrapper.contains(e.target)) {
                        toggleDropdown();
                    }
                });

                // API Pública para injetar opções via JavaScript
                wrapper.updateOptions = function(newOptions) {
                    optionsContainer.innerHTML = '';
                    if (newOptions.length === 0) {
                        optionsContainer.innerHTML = '<div class="px-4 py-3 text-[13px] text-textSub italic empty-state">Nenhuma opção...</div>';
                        return;
                    }
                    newOptions.forEach(opt => {
                        const div = document.createElement('div');
                        div.className = 'custom-select-option px-4 py-3 rounded-md cursor-pointer hover:bg-white/5 text-[13px] text-textValue transition-colors flex items-center justify-between';
                        div.dataset.value = opt.value;
                        div.innerHTML = `<span class="truncate">${opt.label}</span>`;
                        optionsContainer.appendChild(div);
                    });
                };

                // Força visual se já tiver value inicial
                const initialSelected = optionsContainer.querySelector(`[data-value="${input.value}"]`);
                if(initialSelected) selectOption(input.value, initialSelected.querySelector('span').textContent.trim(), false);
            });
        };

        document.addEventListener('DOMContentLoaded', window.initCustomSelects);
    }
</script>