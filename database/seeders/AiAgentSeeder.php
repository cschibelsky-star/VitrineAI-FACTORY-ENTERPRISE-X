<?php

namespace Database\Seeders;

use App\Models\AiAgent;
use App\Models\AiProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AiAgentSeeder extends Seeder
{
    public function run(): void
    {
        $gemini = AiProvider::where('slug', 'gemini')->first();
        $openai = AiProvider::where('slug', 'openai')->first();
        $heygen = AiProvider::where('slug', 'heygen')->first();

        $agents = [
            ['name' => 'Editorial IA', 'type' => 'operacional', 'product_scope' => 'Portal News AI / TV Digital', 'provider' => $gemini, 'description' => 'Reescrita de RSS, expansão editorial, SEO, tags e categorização.'],
            ['name' => 'Repórter IA', 'type' => 'operacional premium', 'product_scope' => 'TV Digital / White Label', 'provider' => $heygen, 'description' => 'Roteiros, avatares e geração de vídeos institucionais.'],
            ['name' => 'Comercial IA', 'type' => 'corporativo', 'product_scope' => 'Centro Comercial', 'provider' => $openai, 'description' => 'Atendimento, diagnóstico, recomendação de plano e proposta comercial.'],
            ['name' => 'Marketing IA', 'type' => 'corporativo', 'product_scope' => 'Marketing', 'provider' => $openai, 'description' => 'Campanhas, anúncios, calendário editorial e conteúdo para redes sociais.'],
            ['name' => 'Turismo IA', 'type' => 'especialista', 'product_scope' => 'Visite Cidade', 'provider' => $gemini, 'description' => 'Roteiros, atrativos, eventos e descrições turísticas.'],
            ['name' => 'Compras IA', 'type' => 'especialista', 'product_scope' => 'Compras Públicas', 'provider' => $openai, 'description' => 'Apoio a DFD, ETP, TR, conferência documental e Lei 14.133.'],
            ['name' => 'Município IA', 'type' => 'especialista', 'product_scope' => 'Município Digital IA', 'provider' => $openai, 'description' => 'Atendimento cidadão, FAQ, serviços públicos e comunicação institucional.'],
            ['name' => 'SISMED IA', 'type' => 'especialista futuro', 'product_scope' => 'SISMED', 'provider' => $openai, 'description' => 'Apoio administrativo, fluxos internos, relatórios e protocolos.'],
        ];

        foreach ($agents as $agent) {
            AiAgent::updateOrCreate(
                ['slug' => Str::slug($agent['name'])],
                [
                    'ai_provider_id' => $agent['provider']?->id,
                    'name' => $agent['name'],
                    'type' => $agent['type'],
                    'product_scope' => $agent['product_scope'],
                    'version' => '1.0',
                    'model_name' => null,
                    'status' => 'online',
                    'is_internal' => in_array($agent['name'], ['Comercial IA', 'Marketing IA'], true),
                    'description' => $agent['description'],
                    'config' => [],
                ]
            );
        }
    }
}
