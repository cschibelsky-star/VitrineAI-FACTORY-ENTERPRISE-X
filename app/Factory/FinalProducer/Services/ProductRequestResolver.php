<?php

declare(strict_types=1);

namespace App\Factory\FinalProducer\Services;

use Illuminate\Support\Str;

class ProductRequestResolver
{
    public function resolve(string $request): array
    {
        $text = Str::of($request)->lower()->ascii()->toString();

        $scores = [
            'gov360' => 0,
            'guia_digital' => 0,
            'portal_news' => 0,
            'tv_digital' => 0,
            'sismed' => 0,
        ];

        foreach (['governo','vender','licitacao','licitacoes','empresa','fornecedor','compras'] as $word) {
            if (str_contains($text, $word)) $scores['gov360'] += 2;
        }

        foreach (['turismo','cidade','guia','evento','atrativo','roteiro'] as $word) {
            if (str_contains($text, $word)) $scores['guia_digital'] += 2;
        }

        foreach (['portal','noticia','noticias','rss','jornal'] as $word) {
            if (str_contains($text, $word)) $scores['portal_news'] += 2;
        }

        foreach (['tv','video','videos','ao vivo','playlist'] as $word) {
            if (str_contains($text, $word)) $scores['tv_digital'] += 2;
        }

        foreach (['saude','paciente','atendimento','unidade','sismed'] as $word) {
            if (str_contains($text, $word)) $scores['sismed'] += 2;
        }

        arsort($scores);

        $product = array_key_first($scores);
        $confidence = (int) current($scores);

        if ($confidence <= 0) {
            $product = 'gov360';
            $confidence = 1;
        }

        return [
            'request' => $request,
            'resolved_product' => $product,
            'confidence_score' => $confidence,
            'scores' => $scores,
            'resolved_at' => now()->toISOString(),
        ];
    }
}
