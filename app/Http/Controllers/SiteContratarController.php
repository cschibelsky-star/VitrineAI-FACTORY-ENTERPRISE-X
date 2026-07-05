<?php

namespace App\Http\Controllers;

use App\CommercialFactory\Services\CommercialFactoryIntakeService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SiteContratarController extends Controller
{
    public function create(): View
    {
        return view('site.contratar', [
            'products' => $this->products(),
            'success' => false,
            'error' => null,
            'responseData' => null,
        ]);
    }

    public function store(Request $request, CommercialFactoryIntakeService $service): View
    {
        $validated = $request->validate([
            'product' => ['required', 'string', 'max:150'],
            'client' => ['required', 'string', 'max:150'],
            'plan' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:180'],
            'domain' => ['nullable', 'string', 'max:180'],
            'phone' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $report = $service->intake([
                'product' => $validated['product'],
                'client' => $validated['client'],
                'plan' => $validated['plan'] ?? 'start',
                'email' => $validated['email'] ?? null,
                'domain' => $validated['domain'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'source' => 'site-laravel-contratar',
                'notes' => $validated['notes'] ?? null,
            ], true);

            return view('site.contratar', [
                'products' => $this->products(),
                'success' => ($report['status'] ?? null) === 'finished',
                'error' => null,
                'responseData' => $report,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return view('site.contratar', [
                'products' => $this->products(),
                'success' => false,
                'error' => 'Não foi possível enviar sua solicitação. Tente novamente ou fale com a Vitrine AI Pro.',
                'responseData' => app()->hasDebugModeEnabled() ? ['error' => $exception->getMessage()] : null,
            ]);
        }
    }

    protected function products(): array
    {
        return [
            'TV Digital Enterprise' => [
                'description' => 'Portal TV com notícias, vídeos, RSS, ao vivo, banners, comercial e IA editorial.',
                'plans' => [
                    'start' => 'Start — R$ 497/mês',
                    'enterprise' => 'Enterprise — R$ 1.500/mês',
                ],
            ],
            'Guia Digital da Cidade' => [
                'description' => 'Guia municipal com turismo, eventos, roteiros, comércio e agenda.',
                'plans' => [
                    'start' => 'Start — R$ 497/mês',
                    'enterprise' => 'Enterprise — R$ 1.500/mês',
                ],
            ],
            'Consultor AI GOV360' => [
                'description' => 'Assistente para pequenas empresas venderem para o governo.',
                'plans' => [
                    'start' => 'Start — R$ 297/mês',
                    'enterprise' => 'Enterprise — R$ 997/mês',
                ],
            ],
        ];
    }
}
