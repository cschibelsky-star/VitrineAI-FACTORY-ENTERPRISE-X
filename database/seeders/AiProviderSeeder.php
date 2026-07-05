<?php

namespace Database\Seeders;

use App\Models\AiProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            ['name' => 'Gemini', 'provider_type' => 'text', 'status' => 'ativo', 'notes' => 'Provedor principal para IA editorial, RSS, resumos e conteúdo.'],
            ['name' => 'OpenAI', 'provider_type' => 'agents', 'status' => 'ativo', 'notes' => 'Provedor para agentes, assistentes e análise avançada.'],
            ['name' => 'HeyGen', 'provider_type' => 'video', 'status' => 'ativo', 'notes' => 'Provedor para Repórter IA, avatares e vídeos.'],
        ];

        foreach ($providers as $provider) {
            AiProvider::updateOrCreate(
                ['slug' => Str::slug($provider['name'])],
                $provider + ['config' => []]
            );
        }
    }
}
