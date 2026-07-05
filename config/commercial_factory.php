<?php

return [
    'products' => [
        'tv_digital_enterprise' => [
            'aliases' => ['tv digital enterprise', 'tv digital', 'portal tv', 'tv'],
            'name' => 'TV Digital Enterprise',
            'factory_prompt' => 'Crie uma TV Digital Enterprise com portal de notícias, vídeos, RSS, transmissão ao vivo, banners, comercial, Repórter IA, editorial por IA, categorias, cidades, colunas e área administrativa. Diretriz: projeto isolado em Aplicações Geradas, sem poluir o Centro Operacional.',
            'plans' => [
                'start' => ['label' => 'Start', 'price' => 497],
                'enterprise' => ['label' => 'Enterprise', 'price' => 1500],
            ],
        ],
        'gov360' => [
            'aliases' => ['gov360', 'consultor ai gov360', 'vender para o governo', 'licitacoes', 'licitações'],
            'name' => 'Consultor AI GOV360',
            'factory_prompt' => 'Crie o Consultor AI GOV360 para pequenas empresas venderem ao governo, com clientes, diagnósticos, documentos, planos de ação, relatórios, oportunidades e checklist. Diretriz: projeto isolado em Aplicações Geradas, sem poluir o Centro Operacional.',
            'plans' => [
                'start' => ['label' => 'Start', 'price' => 297],
                'enterprise' => ['label' => 'Enterprise', 'price' => 997],
            ],
        ],
        'guia_digital' => [
            'aliases' => ['guia digital', 'guia digital da cidade', 'conheca sua cidade', 'conheça sua cidade', 'turismo'],
            'name' => 'Guia Digital da Cidade',
            'factory_prompt' => 'Crie um Guia Digital da Cidade com cidades, atrativos, eventos, roteiros, gastronomia, hospedagens, comércio, categorias, agenda e painel administrativo. Diretriz: projeto isolado em Aplicações Geradas, sem poluir o Centro Operacional.',
            'plans' => [
                'start' => ['label' => 'Start', 'price' => 497],
                'enterprise' => ['label' => 'Enterprise', 'price' => 1500],
            ],
        ],
    ],
];
