<?php

return [
    'gov360' => [
        'name' => 'Consultor AI GOV360',
        'domain' => 'governo_digital',
        'modules' => ['clientes_publicos', 'diagnosticos', 'documentos', 'recomendacoes', 'planos_acao', 'relatorios'],
        'components' => ['dashboard', 'workflow', 'audit_log', 'document_upload', 'ai_recommendations', 'pdf_export'],
    ],

    'guia_digital' => [
        'name' => 'Guia Digital da Cidade',
        'domain' => 'turismo_guia_digital',
        'modules' => ['cidades', 'atrativos', 'eventos', 'roteiros', 'comercios', 'galerias'],
        'components' => ['dashboard', 'map_points', 'gallery', 'event_calendar', 'public_landing'],
    ],

    'portal_news' => [
        'name' => 'Portal News AI',
        'domain' => 'portal_news',
        'modules' => ['materias', 'categorias', 'fontes_rss', 'revisoes', 'videos', 'banners'],
        'components' => ['rss_collector', 'editorial_review', 'seo_tools', 'media_library', 'dashboard'],
    ],

    'tv_digital' => [
        'name' => 'TV Digital Enterprise',
        'domain' => 'tv_digital',
        'modules' => ['programas', 'videos', 'ao_vivo', 'playlists', 'reporteres_ia', 'patrocinadores'],
        'components' => ['video_library', 'live_stream', 'ai_reporter', 'sponsor_banners', 'dashboard'],
    ],

    'sismed' => [
        'name' => 'SISMED',
        'domain' => 'saude_sismed',
        'modules' => ['pacientes', 'unidades', 'profissionais', 'agendamentos', 'atendimentos', 'documentos'],
        'components' => ['patient_history', 'appointment_calendar', 'document_upload', 'access_control', 'dashboard'],
    ],
];
