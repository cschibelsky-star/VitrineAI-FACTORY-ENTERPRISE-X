<?php

namespace App\CommercialFactory\Services;

use Illuminate\Support\Str;

class CommercialProductResolver
{
    public function resolve(string $name): array
    {
        $text = Str::of($name)->lower()->ascii()->toString();

        foreach (config('commercial_factory.products', []) as $key => $product) {
            foreach (($product['aliases'] ?? []) as $alias) {
                $aliasText = Str::of($alias)->lower()->ascii()->toString();

                if (str_contains($text, $aliasText) || str_contains($aliasText, $text)) {
                    return ['key' => $key, 'name' => $product['name'], 'config' => $product];
                }
            }
        }

        return [
            'key' => 'custom_' . Str::slug($name, '_'),
            'name' => $name,
            'config' => [
                'name' => $name,
                'factory_prompt' => 'Crie um sistema para ' . $name . '. Diretriz: projeto isolado em Aplicações Geradas, sem poluir o Centro Operacional.',
                'plans' => ['start' => ['label' => 'Start', 'price' => 497]],
            ],
        ];
    }
}
