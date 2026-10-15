<?php

namespace Modules\Agenda\Http\Controllers;

use App\Core\Domain\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Agenda\Domain\Appointment;
use Modules\Agenda\Domain\Resource;

class AppointmentController
{
    public function index(Request $request): View|JsonResponse
    {
        $appointments = Appointment::query()->orderBy('starts_at')->paginate(20);
        if ($request->expectsJson()) {
            return response()->json($appointments);
        }

        return view('agenda::index', [
            'appointments' => $appointments,
            'resources' => Resource::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $appointment = Appointment::create($this->validateInput($request));

        return $request->expectsJson()
            ? response()->json(['id' => $appointment->ulid, 'status' => $appointment->status], 201)
            : redirect()->route('agenda.index')->with('status', 'Agendamento criado.');
    }

    public function update(Request $request, string $appointment): RedirectResponse|JsonResponse
    {
        $record = Appointment::query()->findOrFail($appointment);
        abort_if($record->status === 'cancelled', 422, 'Agendamento cancelado.');
        $record->update($this->validateInput($request));

        return $request->expectsJson()
            ? response()->json(['id' => $record->ulid, 'status' => $record->status])
            : redirect()->route('agenda.index')->with('status', 'Agendamento atualizado.');
    }

    public function cancel(Request $request, string $appointment): RedirectResponse|JsonResponse
    {
        $record = Appointment::query()->findOrFail($appointment);
        if ($record->status !== 'cancelled') {
            $record->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        }

        return $request->expectsJson()
            ? response()->json(['id' => $record->ulid, 'status' => 'cancelled'])
            : redirect()->route('agenda.index')->with('status', 'Agendamento cancelado.');
    }

    private function validateInput(Request $request): array
    {
        $tenantId = TenantContext::require();

        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'person_id' => [
                'nullable', 'ulid',
                Rule::exists('cadastro_people', 'ulid')->where('tenant_id', $tenantId),
            ],
            'resource_id' => [
                'nullable', 'ulid',
                Rule::exists('agenda_resources', 'ulid')
                    ->where('tenant_id', $tenantId)->where('is_active', true),
            ],
        ]);
    }
}
