<?php

declare(strict_types=1);

namespace App\Factory\Workflow\Services;

use Illuminate\Support\Str;

class WorkflowDesigner
{
    public function design(string $domain): array
    {
        $text = Str::of($domain)->lower()->ascii()->toString();

        $steps = match (true) {
            str_contains($text, 'compras') || str_contains($text, 'licitacao') => [
                'rascunho', 'analise_documental', 'aprovacao', 'publicacao', 'recebimento_propostas', 'julgamento', 'homologacao', 'contrato',
            ],
            str_contains($text, 'turismo') || str_contains($text, 'guia') => [
                'rascunho', 'revisao', 'aprovacao', 'publicado', 'destaque', 'arquivado',
            ],
            str_contains($text, 'saude') || str_contains($text, 'sismed') => [
                'triagem', 'agendado', 'em_atendimento', 'finalizado', 'retorno', 'arquivado',
            ],
            str_contains($text, 'portal') || str_contains($text, 'noticia') => [
                'capturado', 'rascunho_ia', 'revisao_editorial', 'aprovado', 'publicado', 'arquivado',
            ],
            default => ['rascunho', 'analise', 'aprovado', 'ativo', 'arquivado'],
        };

        return [
            'domain' => $domain,
            'steps' => $steps,
            'transitions' => $this->transitions($steps),
            'designed_at' => now()->toISOString(),
        ];
    }

    protected function transitions(array $steps): array
    {
        $transitions = [];

        for ($i = 0; $i < count($steps) - 1; $i++) {
            $transitions[] = [
                'from' => $steps[$i],
                'to' => $steps[$i + 1],
            ];
        }

        return $transitions;
    }
}
