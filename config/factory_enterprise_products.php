<?php

return [
    'gov360' => [
        'name' => 'Consultor AI GOV360',
        'slug' => 'gov360',
        'description' => 'Sistema inteligente para ajudar pequenas empresas a venderem para o governo.',
        'decision_prompt' => 'Crie um sistema GOV360 para pequenas empresas venderem para o governo com clientes, diagnósticos, documentos, planos e relatórios.',
        'modules' => [
            [
                'name' => 'Clientes',
                'slug' => 'clientes',
                'label' => 'Clientes',
                'fields' => [
                    ['name' => 'nome', 'type' => 'string', 'nullable' => false],
                    ['name' => 'documento', 'type' => 'string', 'nullable' => true],
                    ['name' => 'email', 'type' => 'string', 'nullable' => true],
                    ['name' => 'telefone', 'type' => 'string', 'nullable' => true],
                    ['name' => 'cidade', 'type' => 'string', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'ativos', 'inativos'],
            ],
            [
                'name' => 'Diagnósticos',
                'slug' => 'diagnosticos',
                'label' => 'Diagnósticos',
                'fields' => [
                    ['name' => 'cliente_id', 'type' => 'foreignId', 'nullable' => false, 'relationship' => 'belongsTo', 'related_model' => 'Cliente'],
                    ['name' => 'titulo', 'type' => 'string', 'nullable' => false],
                    ['name' => 'descricao', 'type' => 'text', 'nullable' => true],
                    ['name' => 'score', 'type' => 'string', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'abertos', 'concluidos'],
            ],
            [
                'name' => 'Documentos',
                'slug' => 'documentos',
                'label' => 'Documentos',
                'fields' => [
                    ['name' => 'cliente_id', 'type' => 'foreignId', 'nullable' => false, 'relationship' => 'belongsTo', 'related_model' => 'Cliente'],
                    ['name' => 'nome', 'type' => 'string', 'nullable' => false],
                    ['name' => 'tipo', 'type' => 'string', 'nullable' => true],
                    ['name' => 'arquivo', 'type' => 'string', 'nullable' => true],
                    ['name' => 'validade', 'type' => 'date', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'pendentes', 'aprovados'],
            ],
            [
                'name' => 'Planos',
                'slug' => 'planos',
                'label' => 'Planos',
                'fields' => [
                    ['name' => 'cliente_id', 'type' => 'foreignId', 'nullable' => false, 'relationship' => 'belongsTo', 'related_model' => 'Cliente'],
                    ['name' => 'titulo', 'type' => 'string', 'nullable' => false],
                    ['name' => 'descricao', 'type' => 'text', 'nullable' => true],
                    ['name' => 'prazo', 'type' => 'date', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'em_andamento', 'concluidos'],
            ],
            [
                'name' => 'Relatórios',
                'slug' => 'relatorios',
                'label' => 'Relatórios',
                'fields' => [
                    ['name' => 'cliente_id', 'type' => 'foreignId', 'nullable' => false, 'relationship' => 'belongsTo', 'related_model' => 'Cliente'],
                    ['name' => 'titulo', 'type' => 'string', 'nullable' => false],
                    ['name' => 'conteudo', 'type' => 'text', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'emitidos'],
            ],
        ],
    ],
];
