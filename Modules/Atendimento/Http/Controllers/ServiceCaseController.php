<?php

namespace Modules\Atendimento\Http\Controllers;

use App\Core\Domain\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Atendimento\Domain\ServiceCase;

class ServiceCaseController
{
    public function index(Request $request): View|JsonResponse
    {
        $cases = ServiceCase::query()->orderByDesc('opened_at')->paginate(20);

        return $request->expectsJson()
            ? response()->json($cases)
            : view('atendimento::index', ['cases' => $cases]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'person_id' => ['required', 'ulid', Rule::exists('cadastro_people', 'ulid')
                ->where('tenant_id', TenantContext::require())],
            'subject' => ['required', 'string', 'max:180'],
        ]);
        $case = ServiceCase::create($data + ['status' => 'open', 'opened_at' => now()]);

        return $request->expectsJson()
            ? response()->json(['id' => $case->ulid, 'status' => $case->status], 201)
            : redirect()->route('atendimento.index')->with('status', 'Atendimento registrado.');
    }

    public function close(Request $request, string $case): RedirectResponse|JsonResponse
    {
        $record = ServiceCase::query()->findOrFail($case);
        if ($record->status !== 'closed') {
            $record->update(['status' => 'closed', 'closed_at' => now()]);
        }

        return $request->expectsJson()
            ? response()->json(['id' => $record->ulid, 'status' => 'closed'])
            : redirect()->route('atendimento.index')->with('status', 'Atendimento encerrado.');
    }
}
