<?php

declare(strict_types=1);

namespace App\Factory\Decision\Services;

use Illuminate\Support\Str;

class DecisionEngine
{
    public function decide(string $prompt): array
    {
        $text = Str::of($prompt)->lower()->ascii()->toString();

        $domain = match (true) {
            str_contains($text, 'compras') || str_contains($text, 'licitacao') => 'compras_publicas',
            str_contains($text, 'turismo') || str_contains($text, 'guia') || str_contains($text, 'cidade') => 'turismo_guia_digital',
            str_contains($text, 'saude') || str_contains($text, 'sismed') => 'saude_sismed',
            str_contains($text, 'portal') || str_contains($text, 'noticia') => 'portal_news',
            str_contains($text, 'tv') || str_contains($text, 'video') => 'tv_digital',
            str_contains($text, 'governo') || str_contains($text, 'gov360') => 'governo_digital',
            default => 'generico',
        };

        $decisions = [
            'domain' => $domain,
            'modules' => $this->modules($domain),
            'components' => $this->components($domain),
            'dashboards' => ['executive', 'operational', 'quality'],
            'qa_level' => 'strict',
            'install_mode' => 'safe',
            'decision_reason' => 'Domínio identificado por análise textual local e regras de arquitetura da Factory.',
            'decided_at' => now()->toISOString(),
        ];

        return $decisions;
    }

    protected function modules(string $domain): array
    {
        return match ($domain) {
            'compras_publicas' => ['categorias', 'fornecedores', 'licitacoes', 'propostas', 'contratos', 'documentos', 'empenhos'],
            'turismo_guia_digital' => ['cidades', 'atrativos', 'eventos', 'roteiros', 'comercios', 'galerias'],
            'saude_sismed' => ['pacientes', 'unidades', 'profissionais', 'agendamentos', 'atendimentos', 'documentos'],
            'portal_news' => ['materias', 'categorias', 'fontes_rss', 'videos', 'banners', 'revisoes'],
            'tv_digital' => ['programas', 'videos', 'ao_vivo', 'playlists', 'reporteres_ia', 'patrocinadores'],
            'governo_digital' => ['diagnosticos', 'documentos', 'recomendacoes', 'planos_acao', 'relatorios'],
            default => ['registros', 'categorias', 'documentos'],
        };
    }

    protected function components(string $domain): array
    {
        $base = ['dashboard', 'audit_log', 'timeline', 'smart_qa'];

        return array_values(array_unique(array_merge($base, match ($domain) {
            'compras_publicas' => ['workflow', 'document_upload', 'expiration_alerts', 'pdf_export', 'excel_export'],
            'turismo_guia_digital' => ['gallery', 'map_points', 'event_calendar', 'public_landing'],
            'saude_sismed' => ['patient_history', 'appointment_calendar', 'access_control'],
            'portal_news' => ['rss_collector', 'editorial_review', 'seo_tools', 'media_library'],
            'tv_digital' => ['video_library', 'live_stream', 'ai_reporter', 'sponsor_banners'],
            'governo_digital' => ['ai_recommendations', 'document_upload', 'pdf_export', 'workflow'],
            default => ['document_upload'],
        })));
    }
}
