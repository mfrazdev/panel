@extends('layouts.admin')

@section('title', $title)
@section('page_category', 'Recursos')
@section('page_name', $title)

@section('content')
    <div class="flex flex-col animate-[fadeIn_0.4s_ease-out]">
        <!-- Cabeçalho da Página -->
        <div class="flex justify-between items-end mb-10">
            <div>
                <h1 class="text-4xl font-black tracking-tight text-textValue mb-2">{{ $title }}</h1>
                <p class="text-textSub text-sm font-medium">
                    Gerencie a listagem completa de {{ strtolower($title) }}.
                    <span id="search-display-text" style="display: {{ (isset($request) && isset($request->getQuery()['search']) && !empty($request->getQuery()['search'])) ? 'inline' : 'none' }}">
                    @if(isset($request) && isset($request->getQuery()['search']) && !empty($request->getQuery()['search']))
                            <span class="text-primary font-bold ml-1">Resultados para "{{ $request->getQuery()['search'] }}"</span>
                        @endif
                </span>
                </p>
            </div>
            <!-- Botão com cor primária e efeito de brilho no hover -->
            <a href="/admin/{{ $create }}" class="bg-primary hover:brightness-110 text-textValue px-7 py-3.5 rounded-2xl font-bold text-xs uppercase tracking-widest shadow-main transition-all duration-300 flex items-center gap-2 transform hover:-translate-y-1">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Adicionar Novo
            </a>
        </div>

        <!-- Tabela Estilizada (Card usando o color-secondary) -->
        <div class="w-full bg-cards rounded-md shadow-main overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <!-- Cabeçalho mais escuro usando color-sidebar -->
                    <tr class="bg-sidebar">
                        @foreach($map as $column)
                            <th class="px-10 py-7 text-textSub text-[11px] font-black uppercase tracking-[0.2em] whitespace-nowrap">
                                {{ $column['label'] }}
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody class="divide-none">
                    @forelse($resources as $item)
                        <!-- Linha inteira clicável com hover indo para bg-terciary -->
                        <tr class="transition-colors duration-200 hover:bg-terciary group data-row cursor-pointer" onclick="handleRowClick('/admin/{{ str_replace('[id]', $item->id, $see) }}')">
                            @foreach($map as $column)
                                <td class="px-10 py-6">
                                    @php
                                        $val = (is_array($item) || $item instanceof \ArrayAccess)
    ? ($item[$column['key']] ?? null)
    : ($item->{$column['key']} ?? null);
                                        $type = $column['type'] ?? 'text';
                                    @endphp

                                    @if($type === 'text')
                                        <span class="text-[14px] font-semibold text-textValue">{{ $val }}</span>

                                    @elseif($type === 'badge')
                                        <!-- Badges usando vars de success/danger -->
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest {{ $val == 'active' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }}">
                                            @if($val == 'active')
                                                <span class="w-1.5 h-1.5 rounded-full bg-success mr-2 shadow-[0_0_8px_var(--color-success)]"></span>
                                            @else
                                                <span class="w-1.5 h-1.5 rounded-full bg-danger mr-2 shadow-[0_0_8px_var(--color-danger)]"></span>
                                            @endif
                                            {{ $val }}
                                        </span>

                                    @elseif($type === 'user')
                                        <div class="flex items-center gap-4">
                                            <div class="w-11 h-11 rounded-2xl bg-primary/10 flex items-center justify-center text-primary font-black text-sm shadow-inner">
                                                {{ strtoupper(substr($val, 0, 2)) }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[15px] text-textValue font-bold">{{ $val }}</span>
                                                <span class="text-[12px] text-textSub font-medium">{{ (is_array($item) || $item instanceof \ArrayAccess) ? ($item['email'] ?? 'Sem e-mail') : ($item->email ?? 'Sem e-mail') }}</span>
                                            </div>
                                        </div>

                                    @elseif($type === 'date')
                                        <span class="text-[14px] font-medium text-textSub">
                                            {{ date('d M, Y', strtotime($val)) }}
                                        </span>

                                    @elseif($type === 'custom')
                                        @if(isset($column['template']))
                                            @include($column['template'], ['item' => $item, 'val' => $val, 'column' => $column])
                                        @else
                                            <span class="text-danger">Template não definido</span>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr id="server-empty-state">
                            <td colspan="{{ count($map) }}" class="py-32 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-16 h-16 rounded-3xl bg-bgBase flex items-center justify-center text-textSub mb-2 shadow-inner">
                                        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>
                                    <p class="text-textValue font-black text-lg tracking-tight">Nenhum registro encontrado.</p>
                                    <p class="text-textSub text-sm font-medium">Ainda não há dados cadastrados nesta seção.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    <tr id="js-empty-state" style="display: none;">
                        <td colspan="{{ count($map) }}" class="py-32 text-center">
                            <div class="flex flex-col items-center gap-4">
                                <div class="w-16 h-16 rounded-3xl bg-bgBase flex items-center justify-center text-textSub mb-2 shadow-inner">
                                    <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <p class="text-textValue font-black text-lg tracking-tight">Nada encontrado para a sua busca.</p>
                                <p class="text-textSub text-sm font-medium">Tente pesquisar usando outros termos ou limpe o campo de busca.</p>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }


        /* Estilização Customizada do Scroll Horizontal */
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: rgba(156, 163, 175, 0.5) transparent; /* Suporte p/ Firefox */
        }
        .custom-scrollbar::-webkit-scrollbar {
            height: 8px; /* Altura do scroll horizontal */
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.4); /* Cinza translúcido */
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: rgba(156, 163, 175, 0.7); /* Fica mais escuro no hover */
        }

        /* Garantia para o hover da linha funcionar caso a classe do tailwind falhe com opacidade */
        tr.data-row:hover {
            background-color: var(--color-terciary, rgba(156, 163, 175, 0.1)) !important;
        }
    </style>

    <script>
        // Função para lidar com o clique na linha, ignorando se houver texto selecionado
        window.handleRowClick = function(url) {
            const selection = window.getSelection().toString();
            if (selection.length > 0) {
                return; // Se o usuário selecionou texto (arrastou o mouse), não redireciona
            }
            window.location.href = url;
        };

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            if (!searchInput) return;

            const form = searchInput.closest('form');
            if (form) {
                form.addEventListener('submit', function(e) { e.preventDefault(); });
            }

            const dataRows = document.querySelectorAll('tr.data-row');
            const jsEmptyState = document.getElementById('js-empty-state');
            const searchDisplayText = document.getElementById('search-display-text');

            function filterTable(searchTerm) {
                const term = searchTerm.toLowerCase().trim();
                let visibleCount = 0;

                dataRows.forEach(row => {
                    const textContent = row.textContent.toLowerCase();
                    if (textContent.includes(term)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (jsEmptyState) {
                    if (visibleCount === 0 && dataRows.length > 0) {
                        jsEmptyState.style.display = '';
                    } else {
                        jsEmptyState.style.display = 'none';
                    }
                }

                if (searchDisplayText) {
                    if (term !== '') {
                        searchDisplayText.innerHTML = `<span class="text-primary font-bold ml-1">Resultados para "${searchTerm}"</span>`;
                        searchDisplayText.style.display = 'inline';
                    } else {
                        searchDisplayText.style.display = 'none';
                    }
                }
            }

            searchInput.addEventListener('input', function(e) { filterTable(e.target.value); });
            if (searchInput.value) { filterTable(searchInput.value); }
        });
    </script>

    @if(isset($extra_scripts) && is_array($extra_scripts))
        @foreach($extra_scripts as $script)
            @include($script)
        @endforeach
    @endif
@endsection