@extends('layouts.admin')

@section('title', $title)
@section('page_category', 'Recursos')
@section('page_name', $title)

@section('content')
    <div class="flex flex-col animate-[fadeIn_0.4s_ease-out]">
        <!-- Cabeçalho da Página -->
        <div class="flex justify-between items-end mb-6">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-textValue mb-2">{{ $title }}</h1>
                <p class="text-textSub text-sm font-medium">
                    Gerencie a listagem completa de {{ strtolower($title) }}.
                    <span id="search-display-text" style="display: {{ (isset($_GET['search']) && !empty($_GET['search'])) ? 'inline' : 'none' }}">
                        @if(isset($_GET['search']) && !empty($_GET['search']))
                            <span class="text-primary font-bold ml-1">Resultados para "{{ $_GET['search'] }}"</span>
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

        @if(isset($pagination) || isset($filters))
            <!-- Barra de Filtros e Busca Inteligente -->
            <form method="GET" action="" id="filter-form" class="mb-6 flex flex-wrap gap-4 items-center bg-cards border border-white/5 p-4 rounded-xl shadow-sm">

                <!-- Filtros Dinâmicos -->
                @if(isset($filters) && is_array($filters))
                    @foreach($filters as $key => $filterData)
                        @php
                            // Aceita o novo formato do array de filtros
                            $filterName = $filterData['name'] ?? ucfirst($key);
                            $filterOptions = $filterData['keys'] ?? [];

                            // Garante que tenha a opção padrão para limpar o filtro
                            $opcoesComDefault = ['' => 'Filtrar ' . $filterName . '...'] + $filterOptions;
                        @endphp
                        <div class="min-w-[220px] flex-1 md:flex-none">
                            @include('partials.components.custom-select', [
                                'id' => 'filter-' . $key,
                                'name' => $key,
                                'placeholder' => 'Filtrar ' . $filterName . '...',
                                'value' => $_GET[$key] ?? '',
                                'options' => $opcoesComDefault
                            ])
                        </div>
                    @endforeach
                @endif

                <!-- Itens por página -->
                <div class="min-w-[160px]">
                    @include('partials.components.custom-select', [
                        'id' => 'filter-per-page',
                        'name' => 'per_page',
                        'placeholder' => 'Itens por pág.',
                        'value' => $_GET['per_page'] ?? '10',
                        'options' => [
                            '10' => '10 por pág.',
                            '25' => '25 por pág.',
                            '50' => '50 por pág.',
                            '100' => '100 por pág.'
                        ]
                    ])
                </div>

                <!-- Botão de Limpar (Aparece se houver algum filtro ativo) -->
                @if(!empty(array_diff_key($_GET, array_flip(['page', 'per_page']))))
                    <a href="{{ strtok($_SERVER['REQUEST_URI'] ?? '', '?') }}" class="px-4 py-2.5 text-textSub hover:text-danger text-sm font-bold transition-colors flex items-center gap-1">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Limpar
                    </a>
                @endif

                <!-- Input hidden para manter a página atual em caso de nova busca/filtro e resetar se necessário -->
                @if(isset($_GET['page']))
                    <input type="hidden" name="page" value="1">
                @endif
            </form>
        @endif


        <!-- Card da Tabela: Cor sólida, borda translúcida -->
        <div class="w-full bg-cards border border-white/5 rounded-xl shadow-main overflow-hidden flex flex-col">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead class="border-b border-white/10 bg-white/[0.01]">
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

                                    @elseif($type === 'link')
                                        @php
                                            $url = $column['url'] ?? '#';
                                            // Substitui o placeholder dinâmico (ex: [ownerId]) pelo valor real contido na linha atual
                                            if (isset($column['url_key'])) {
                                                $urlKeyVal = (is_array($item) || $item instanceof \ArrayAccess)
                                                    ? ($item[$column['url_key']] ?? '')
                                                    : ($item->{$column['url_key']} ?? '');
                                                $url = str_replace('[' . $column['url_key'] . ']', $urlKeyVal, $url);
                                            }
                                        @endphp
                                                <!-- event.stopPropagation() previne o click na linha que leva para edicao do server -->
                                        <a href="{{ $url }}" class="text-primary hover:brightness-125 font-bold hover:underline transition-all" onclick="event.stopPropagation()">
                                            {{ $val }}
                                        </a>

                                    @elseif($type === 'badge')
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
                                                    $meses = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
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
                                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>
                                    <p class="text-textValue font-bold text-base tracking-tight">Nenhum registro encontrado.</p>
                                    <p class="text-textSub text-sm font-medium">Não há dados para os filtros atuais.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    <!-- Empty state gerado pelo JS na busca -->
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

            <!-- Footer com Paginação Customizada -->
            @if(isset($pagination) && is_array($pagination) && !empty($pagination))
                @php
                    $currentPage = $pagination['current_page'] ?? 1;
                    $lastPage = $pagination['last_page'] ?? 1;
                    $firstItem = $pagination['from'] ?? 0;
                    $lastItem = $pagination['to'] ?? 0;
                    $totalItems = $pagination['total'] ?? 0;
                @endphp
                <div class="px-8 py-5 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-4 bg-white/[0.01]">
                    <div class="text-sm font-medium text-textSub">
                        Mostrando <span class="text-textValue font-bold">{{ $firstItem }}</span> até <span class="text-textValue font-bold">{{ $lastItem }}</span> de <span class="text-textValue font-bold">{{ $totalItems }}</span> resultados
                    </div>

                    @if($lastPage > 1)
                        <div class="flex items-center gap-1">
                            <!-- Anterior -->
                            @if ($currentPage <= 1)
                                <span class="w-9 h-9 flex items-center justify-center rounded-lg border border-white/5 bg-transparent text-white/20 cursor-not-allowed">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path></svg>
                            </span>
                            @else
                                <a href="?{{ http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) }}" class="w-9 h-9 flex items-center justify-center rounded-lg border border-white/10 bg-white/5 text-textSub hover:bg-white/10 hover:text-textValue transition-colors">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path></svg>
                                </a>
                            @endif

                            <!-- Números das Páginas (Exibe algumas próximas) -->
                            @foreach(range(max(1, $currentPage - 2), min($lastPage, $currentPage + 2)) as $i)
                                @if($i == $currentPage)
                                    <span class="w-9 h-9 flex items-center justify-center rounded-lg border border-primary bg-primary/10 text-primary font-bold text-sm">
                                    {{ $i }}
                                </span>
                                @else
                                    <a href="?{{ http_build_query(array_merge($_GET, ['page' => $i])) }}" class="w-9 h-9 flex items-center justify-center rounded-lg border border-white/10 bg-transparent text-textSub hover:bg-white/10 hover:text-textValue transition-colors text-sm font-medium">
                                        {{ $i }}
                                    </a>
                                @endif
                            @endforeach

                            <!-- Próxima -->
                            @if ($currentPage >= $lastPage)
                                <span class="w-9 h-9 flex items-center justify-center rounded-lg border border-white/5 bg-transparent text-white/20 cursor-not-allowed">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                            </span>
                            @else
                                <a href="?{{ http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) }}" class="w-9 h-9 flex items-center justify-center rounded-lg border border-white/10 bg-white/5 text-textSub hover:bg-white/10 hover:text-textValue transition-colors">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

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

        /* Ajuste fino para os selects no modo escuro */
        select option {
            background-color: #09090b; /* Ajuste para a cor de fundo do seu tema */
            color: #fff;
        }
    </style>

    <script>
        // Função para lidar com o clique na linha, ignorando se houver texto selecionado
        window.handleRowClick = function(url) {
            const selection = window.getSelection().toString();
            if (selection.length > 0) {
                return;
            }
            window.location.href = url;
        };

        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('filter-form');
            const searchInput = document.querySelector('input[name="search"]'); // Pega de onde quer que ele esteja
            const dataRows = document.querySelectorAll('tr.data-row');
            const jsEmptyState = document.getElementById('js-empty-state');
            const searchDisplayText = document.getElementById('search-display-text');

            // Lida com o filtro em tempo real via JS para o campo de pesquisa
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

            if (searchInput) {
                // Impede que o form de busca faça submit e recarregue a página se a ideia for só o live search da tabela local
                const searchForm = searchInput.closest('form');
                if (searchForm && searchForm.id !== 'filter-form') {
                    searchForm.addEventListener('submit', function(e) { e.preventDefault(); });
                }

                // Ao digitar, filtra a tabela (live search)
                searchInput.addEventListener('input', function(e) { filterTable(e.target.value); });

                // Impede que o enter dê submit se o foco estiver na pesquisa (opcional, mas evita recarregar a página atoa já que é live)
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                    }
                });

                if (searchInput.value) { filterTable(searchInput.value); }
            }

            // Submete o formulário caso os SELECTS sejam alterados
            if (filterForm) {
                filterForm.addEventListener('change', function(e) {
                    if (e.target.tagName === 'INPUT' && e.target.type === 'hidden') {
                        filterForm.submit();
                    }
                });
            }
        });
    </script>

    @if(isset($extra_scripts) && is_array($extra_scripts))
        @foreach($extra_scripts as $script)
            @include($script)
        @endforeach
    @endif
@endsection