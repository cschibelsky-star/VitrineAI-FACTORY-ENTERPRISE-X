<?php

namespace App\Http\Controllers\Api;

use App\CommercialFactory\Services\CommercialFactoryIntakeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\SiteFactoryIntakeRequest;
use Illuminate\Http\JsonResponse;
use Throwable;

class SiteFactoryIntakeController extends Controller
{
    public function __invoke(
        SiteFactoryIntakeRequest $request,
        CommercialFactoryIntakeService $service
    ): JsonResponse {
        try {
            $report = $service->intake([
                'product' => $request->string('product')->toString(),
                'client' => $request->string('client')->toString(),
                'plan' => $request->string('plan')->toString(),
                'email' => $request->input('email'),
                'domain' => $request->input('domain'),
                'phone' => $request->input('phone'),
                'source' => $request->string('source')->toString(),
                'notes' => $request->input('notes'),
            ], (bool) config('site_factory.dry_run_default', true));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'status' => 'failed',
                'message' => 'Não foi possível processar o pedido comercial.',
                'error' => app()->hasDebugModeEnabled() ? $exception->getMessage() : null,
            ], 500);
        }

        return response()->json([
            'ok' => $report['status'] === 'simulated',
            'execution_mode' => $report['execution_mode'] ?? 'unknown',
            'real_build_performed' => $report['real_build_performed'] ?? false,
            'status' => $report['status'],
            'commercial_status' => $report['commercial_status'] ?? null,
            'project_slug' => $report['project_slug'] ?? null,
            'report_path' => $report['path'] ?? null,
            'message' => $report['status'] === 'simulated'
                ? 'Pedido recebido e simulado. Nenhum build real foi executado ou enviado para homologação.'
                : 'Pedido recebido, mas a simulação da Factory retornou falha.',
        ], $report['status'] === 'simulated' ? 202 : 422);
    }
}
