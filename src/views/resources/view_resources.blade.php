@extends('layouts.admin')

@section('title', $title)
@section('page_category', 'Recursos')
@section('page_name', $title)

@section('content')
    <div class="flex flex-col animate-[fadeIn_0.4s_ease-out]">
        <!-- Cabeçalho da Página -->
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-textValue mb-2">{{ $title }}</h1>
                <p class="text-textSub text-sm font-medium">
                    Gerencie a listagem completa de {{ strtolower($title) }}.
                    <span id="search-display-text" style="display: {{ (isset($request) && isset($request->getQuery()['search']) && !empty($request->getQuery()['search'])) ? 'inline' : 'none' }}">
                    @if(isset($request) && isset($request->getQuery()['search']) && !empty($request->getQuery()['search']))
                            <span class="text-primary font-bold ml-1">Resultados para "{{ $request->getQuery()['search'] }}"</span>
                        @endif
                </span>
                </p>
            </div>
            <!-- Botão de Adicionar -->
            <a href="/admin/{{ $create }}" class="bg-primary hover:brightness-110 text-[#09090b] px-6 py-3 rounded-xl font-bold text-sm shadow-main transition-all duration-300 flex items-center gap-2 transform hover:-translate-y-0.5">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Novo Registro
            </a>
        </div>

        <!-- Card da Tabela: Cor sólida, borda translúcida -->
        <div class="w-full bg-cards border border-white/5 rounded-xl shadow-main overflow-hidden flex flex-col">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <!-- Cabeçalho sem bg diferente, apenas uma linha separadora -->
                    <thead class="border-b border-white/10">
                    <tr>
                        @foreach($map as $column)
                            <th class="px-8 py-5 text-textSub text-[12px] font-bold uppercase tracking-wider whitespace-nowrap">
                                {{ $column['label'] }}
                            </th>
                        @endforeach
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-white/5">
                    @forelse($resources as $item)
                        <!-- Hover super sutil usando branco translúcido -->
                        <tr class="transition-colors duration-200 hover:bg-white/[0.02] group data-row cursor-pointer" onclick="handleRowClick('/admin/{{ str_replace('[id]', $item->id, $see) }}')">
                            @foreach($map as $column)
                                <td class="px-8 py-5">
                                    @php
                                        $val = (is_array($item) || $item instanceof \ArrayAccess)
                                            ? ($item[$column['key']] ?? null)
                                            : ($item->{$column['key']} ?? null);
                                        $type = $column['type'] ?? 'text';
                                    @endphp

                                    @if($type === 'text')
                                        <span class="text-[14px] font-semibold text-textValue">{{ $val }}</span>

                                    @elseif($type === 'badge')
                                        <!-- Badges usando vars de success/danger sem borda pesada -->
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-[11px] font-black uppercase tracking-widest {{ $val == 'active' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }}">
                                            @if($val == 'active')
                                                <span class="w-1.5 h-1.5 rounded-full bg-success mr-2 shadow-[0_0_8px_var(--color-success)]"></span>
                                            @else
                                                <span class="w-1.5 h-1.5 rounded-full bg-danger mr-2 shadow-[0_0_8px_var(--color-danger)]"></span>
                                            @endif
                                            {{ $val }}
                                        </span>

                                    @elseif($type === 'user')
                                        <div class="flex items-center gap-4">
                                            <img src="https://www.gravatar.com/avatar/{{ md5(strtolower(trim((is_array($item) || $item instanceof \ArrayAccess) ? ($item['email'] ?? '') : ($item->email ?? '')))) }}?s=80&d=mp"
                                                 alt="{{ $val }}"
                                                 class="w-10 h-10 rounded-full shrink-0 object-cover shadow-sm">

                                            <div class="flex flex-col">
                                                <span class="text-[14px] text-textValue font-bold">{{ $val }}</span>
                                                <span class="text-[12px] text-textSub font-medium">{{ (is_array($item) || $item instanceof \ArrayAccess) ? ($item['email'] ?? 'Sem e-mail') : ($item->email ?? 'Sem e-mail') }}</span>
                                            </div>
                                        </div>

                                    @elseif($type === 'date')
                                        <span class="text-[14px] font-medium text-textSub">
    @php
        if ($val) {
            $meses = [
                '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr',
                '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
                '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'
            ];
            $time = strtotime($val);
            echo date('d', $time) . ' ' . $meses[date('m', $time)] . ', ' . date('Y', $time);
        } else {
            echo '-';
        }
    @endphp
</span>

                                    @elseif($type === 'custom')
                                        @if(isset($column['template']))
                                            @include($column['template'], ['item' => $item, 'val' => $val, 'column' => $column])
                                        @else
                                            <span class="text-danger text-sm">Template não definido</span>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr id="server-empty-state">
                            <td colspan="{{ count($map) }}" class="py-24 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-textSub mb-2 shadow-sm">
                                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>
                                    <p class="text-textValue font-bold text-base tracking-tight">Nenhum registro encontrado.</p>
                                    <p class="text-textSub text-sm font-medium">Ainda não há dados cadastrados nesta seção.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    <tr id="js-empty-state" style="display: none;">
                        <td colspan="{{ count($map) }}" class="py-24 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-textSub mb-2 shadow-sm">
                                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <p class="text-textValue font-bold text-base tracking-tight">Nada encontrado para a sua busca.</p>
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
            scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
        }
        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 255, 255, 0.2);
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