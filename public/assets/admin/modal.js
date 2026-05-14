/**
 * Hight Cloud - Modal de Confirmação Dinâmico
 * Atualizado para seguir perfeitamente as variáveis CSS do painel (--color-cards, --color-sidebar, etc.)
 */

window.AdminModal = {
    /**
     * Exibe o modal de confirmação de exclusão.
     * @param {Object} options Configurações do modal
     * @param {string} options.title Título do modal (ex: 'Excluir Usuário')
     * @param {string} options.message Mensagem de aviso (ex: 'Tem certeza que deseja excluir o usuário X?')
     * @param {function} options.onConfirm Função executada ao clicar em "Excluir"
     */
    confirmDelete: function(options) {
        // Valores padrão
        const title = options.title || 'Excluir Registro';
        const message = options.message || 'Tem certeza que deseja excluir este registro? Essa ação não pode ser desfeita.';

        // Remove modal anterior se existir (prevenção)
        const existingModal = document.getElementById('hightcloud-delete-modal');
        if (existingModal) existingModal.remove();

        // Estrutura HTML do Modal usando as classes globais do Tailwind e as variáveis CSS do seu :root
        const modalHtml = `
            <div id="hightcloud-delete-modal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 transition-opacity duration-300" style="pointer-events: none; font-family: var(--font-inter);">
                <!-- Backdrop com blur e escurecimento -->
                <div class="absolute inset-0 bg-black/70 backdrop-blur-sm modal-backdrop transition-opacity duration-300"></div>
                
                <!-- Caixa do Modal -->
                <div class="relative bg-cards w-full max-w-md rounded-2xl shadow-main p-8 transform scale-95 transition-all duration-300 modal-content border border-white/10 flex flex-col gap-6">
                    
                    <!-- Cabeçalho / Ícone -->
                    <div class="flex gap-5 items-start">
                        <!-- Ícone de Atenção Inset -->
                        <div class="w-14 h-14 rounded-2xl bg-danger/10 border border-danger/20 flex items-center justify-center text-danger shadow-inner flex-shrink-0">
                            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div class="flex flex-col gap-1.5 pt-1">
                            <h3 class="text-xl font-black text-textValue tracking-tight leading-none">${title}</h3>
                            <p class="text-[13px] font-medium text-textSub leading-relaxed">${message}</p>
                        </div>
                    </div>
                    
                    <!-- Divisor -->
                    <div class="w-full h-[1px] bg-white/5"></div>
                    
                    <!-- Botões de Ação -->
                    <div class="flex items-center justify-end gap-3">
                        <button id="hc-modal-cancel" class="px-6 py-3 rounded-xl text-[13px] font-bold text-textSub hover:text-textValue bg-sidebar hover:bg-terciary transition-all shadow-sm border border-transparent">
                            Cancelar
                        </button>
                        <button id="hc-modal-confirm" class="px-6 py-3 rounded-xl text-[13px] font-bold text-textValue bg-danger/10 hover:bg-danger border border-danger/20 hover:border-danger transition-all flex items-center gap-2 shadow-sm transform hover:-translate-y-0.5 group">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" class="group-hover:animate-bounce">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 114 0v2" />
                            </svg>
                            Confirmar Exclusão
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Adiciona ao final do body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        const modalEl = document.getElementById('hightcloud-delete-modal');
        const contentEl = modalEl.querySelector('.modal-content');
        const btnCancel = document.getElementById('hc-modal-cancel');
        const btnConfirm = document.getElementById('hc-modal-confirm');
        const backdrop = modalEl.querySelector('.modal-backdrop');

        // Função para fechar com animação suave
        const closeModal = () => {
            modalEl.classList.remove('opacity-100');
            contentEl.classList.remove('scale-100');

            modalEl.classList.add('opacity-0');
            contentEl.classList.add('scale-95');

            setTimeout(() => {
                modalEl.remove();
            }, 300); // Tempo igual ao duration-300 do tailwind
        };

        // Animação de entrada (Trigger via pequeno delay para o DOM processar a renderização)
        setTimeout(() => {
            modalEl.style.pointerEvents = 'auto';

            modalEl.classList.remove('opacity-0');
            contentEl.classList.remove('scale-95');

            modalEl.classList.add('opacity-100');
            contentEl.classList.add('scale-100');
        }, 20);

        // Eventos de clique para fechar
        btnCancel.addEventListener('click', closeModal);
        backdrop.addEventListener('click', closeModal);

        // Ação de confirmar exclusão
        btnConfirm.addEventListener('click', () => {
            // Desabilita o botão, muda a cor para "Processando" e troca o texto
            btnConfirm.disabled = true;
            btnConfirm.className = "px-6 py-3 rounded-xl text-[13px] font-bold text-textSub bg-sidebar border border-white/5 transition-all flex items-center gap-2 cursor-wait opacity-80";
            btnConfirm.innerHTML = `
                <svg class="animate-spin h-4 w-4 text-primary" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Excluindo...
            `;

            if (options.onConfirm && typeof options.onConfirm === 'function') {
                options.onConfirm();
            }
        });

        // Fechar pressionando a tecla 'ESC'
        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escHandler); // Auto-cleanup do evento
            }
        });
    }
};