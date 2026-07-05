<?php

declare(strict_types=1);

namespace App\Factory\Products\Services;

use Illuminate\Support\Facades\File;

class ProductGenerator
{
    public function generate(string $productKey): array
    {
        $products = config('factory_products', []);

        if (! isset($products[$productKey])) {
            throw new \RuntimeException("Produto não encontrado no catálogo: {$productKey}");
        }

        $product = $products[$productKey];

        $manifest = [
            'key' => $productKey,
            'name' => $product['name'],
            'domain' => $product['domain'],
            'modules' => $product['modules'],
            'components' => $product['components'],
            'status' => 'planned',
            'generated_by' => 'FACTORY_MACRO_PACK_01_v3.0',
            'generated_at' => now()->toISOString(),
        ];

        $dir = storage_path('app/factory/products/' . $productKey);
        File::ensureDirectoryExists($dir);

        $path = $dir . '/product_manifest.json';
        File::put($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $manifest['path'] = $path;

        return $manifest;
    }
}
