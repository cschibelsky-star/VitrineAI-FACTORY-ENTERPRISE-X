<x-filament-panels::page>
    <div class="space-y-8">

        {{-- HERO --}}
        <div class="relative overflow-hidden rounded-3xl bg-gray-950 p-8 text-white shadow-sm">
            <div class="absolute inset-0 opacity-20">
                <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-primary-500 blur-3xl"></div>
                <div class="absolute -bottom-24 left-10 h-72 w-72 rounded-full bg-cyan-500 blur-3xl"></div>
            </div>

            <div class="relative z-10 grid gap-8 md:grid-cols-2 md:items-center">
                <div>
                    <div class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs uppercase tracking-widest text-primary-200">
                        Vitrine AI Pro Enterprise
                    </div>

                    <h1 class="mt-5 text-4xl font-bold tracking-tight">
                        Centro Operacional Inteligente
                    </h1>

                    <p class="mt-4 max-w-2xl text-sm leading-6 text-gray-300">
                        Gestão executiva da Vitrine AI Pro com visão integrada de clientes, produtos, licenças,
                        operação comercial e Factory Studio. As aplicações geradas ficam isoladas em Projetos.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="/admin/factory-studio-enterprise" class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">
                            Abrir Factory Studio
                        </a>
                        <a href="/admin/generated-projects" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">
                            Ver Projetos
                        </a>
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                    <div class="text-xs uppercase tracking-widest text-gray-400">Status da Plataforma</div>

                    <div class="mt-5 space-y-4">
                        @foreach ($this->getFactoryPipeline() as $item)
                            <div class="flex items-center justify-between rounded-xl bg-white/5 px-4 py-3">
                                <span class="text-sm text-gray-200">{{ $item['step'] }}</span>
                                <span class="rounded-full bg-primary-500/20 px-3 py-1 text-xs font-semibold text-primary-200">
                                    {{ $item['status'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- EXECUTIVE CARDS --}}
        <div class="grid gap-6 md:grid-cols-4">
            @foreach ($this->getStats() as $card)
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</div>
                            <div class="mt-3 text-4xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $card['value'] }}</div>
                        </div>
                        <div class="rounded-xl bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            {{ $card['type'] }}
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ $card['trend'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- MAIN GRID --}}
        <div class="grid gap-6 lg:grid-cols-3">

            {{-- OPERATION --}}
            <div class="lg:col-span-2 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-950 dark:text-white">Operação Vitrine AI Pro</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Visão executiva dos produtos e frentes comerciais do ecossistema.
                        </p>
                    </div>
                    <span class="rounded-full bg-success-500/10 px-3 py-1 text-xs font-semibold text-success-600">
                        Ativo
                    </span>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach ($this->getProducts() as $product)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-800 dark:bg-gray-950">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-semibold text-gray-950 dark:text-white">{{ $product['name'] }}</h3>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $product['desc'] }}</p>
                                </div>
                                <span class="whitespace-nowrap rounded-full bg-primary-500/10 px-3 py-1 text-xs font-semibold text-primary-600">
                                    {{ $product['status'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- SIDE PANEL --}}
            <div class="space-y-6">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-lg font-bold text-gray-950 dark:text-white">Factory Studio</h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Produção de sistemas com IA, blueprints, builds, QA e publicação controlada.
                    </p>

                    <div class="mt-5 space-y-3">
                        @foreach (['IA Arquiteta', 'IA Desenvolvedora', 'IA QA', 'IA Deploy'] as $agent)
                            <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 dark:bg-gray-950">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $agent }}</span>
                                <span class="text-xs text-success-600">online</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-lg font-bold text-gray-950 dark:text-white">Próximas Ações</h2>
                    <div class="mt-5 space-y-3">
                        @foreach (['Homologar Projetos', 'Publicar Marketplace', 'Revisar Builds', 'Atualizar Dashboard'] as $task)
                            <div class="rounded-xl border border-dashed border-gray-300 p-3 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                {{ $task }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- PROJECTS / MARKETPLACE --}}
        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-950 dark:text-white">Projetos Gerados</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Aplicações produzidas pela Factory em workspaces isolados.
                        </p>
                    </div>
                    <a href="/admin/generated-projects" class="text-sm font-semibold text-primary-600">Abrir</a>
                </div>

                <div class="mt-5 grid gap-3">
                    @foreach (['GOV360', 'Clínicas Veterinárias', 'Gestão de Licitações'] as $project)
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 dark:bg-gray-950">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $project }}</span>
                            <span class="rounded-full bg-warning-500/10 px-3 py-1 text-xs font-semibold text-warning-600">homologação</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-950 dark:text-white">Marketplace</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Produtos homologados, templates e componentes comerciais.
                        </p>
                    </div>
                    <a href="/admin/marketplace-enterprise" class="text-sm font-semibold text-primary-600">Abrir</a>
                </div>

                <div class="mt-5 grid gap-3">
                    @foreach (['Consultor AI GOV360', 'Guia Digital da Cidade', 'TV Digital Enterprise'] as $product)
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 dark:bg-gray-950">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $product }}</span>
                            <span class="rounded-full bg-gray-500/10 px-3 py-1 text-xs font-semibold text-gray-500">roadmap</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
