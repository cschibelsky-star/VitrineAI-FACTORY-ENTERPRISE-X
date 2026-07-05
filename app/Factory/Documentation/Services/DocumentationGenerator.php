<?php

declare(strict_types=1);

namespace App\Factory\Documentation\Services;

use Illuminate\Support\Facades\File;

class DocumentationGenerator
{
    public function generate(string $productKey): array
    {
        $productPath = storage_path('app/factory/products/' . $productKey . '/product_manifest.json');

        if (! File::exists($productPath)) {
            throw new \RuntimeException("Manifesto do produto não encontrado. Rode factory:product {$productKey} antes.");
        }

        $manifest = json_decode((string) File::get($productPath), true);

        $markdown = "# {$manifest['name']}\n\n";
        $markdown .= "## Domínio\n\n{$manifest['domain']}\n\n";
        $markdown .= "## Módulos\n\n";
        foreach ($manifest['modules'] as $module) {
            $markdown .= "- {$module}\n";
        }
        $markdown .= "\n## Componentes\n\n";
        foreach ($manifest['components'] as $component) {
            $markdown .= "- {$component}\n";
        }
        $markdown .= "\n## Status\n\n{$manifest['status']}\n\n";
        $markdown .= "Gerado por FACTORY_MACRO_PACK_01 v3.0.\n";

        $dir = storage_path('app/factory/docs/' . $productKey);
        File::ensureDirectoryExists($dir);

        $path = $dir . '/DOCUMENTACAO_TECNICA.md';
        File::put($path, $markdown);

        return [
            'product' => $productKey,
            'path' => $path,
        ];
    }
}
