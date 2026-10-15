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
use Modules\Cadastro\Contracts\PeopleDirectory;
use Modules\Agenda\Domain\Availability;
use Modules\Agenda\Application\Scheduling;

class AppointmentController
{
    public function __construct(private PeopleDirectory $people, private Scheduling $scheduling) {}

    public function index(Request $request): View|JsonResponse
    {
        $appointments = Appointment::query()->orderBy('starts_at')->paginate(20);
        if ($request->expectsJson()) {
            return response()->json($appointments);
        }

        return view('agenda::index', [
            'appointments' => $appointments,
            'resources' => Resource::query()->orderBy('name')->get(),
            'availability' => Availability::query()->orderBy('weekday')->orderBy('starts_at')->get(),
            'people' => $this->people->options(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $appointment = $this->scheduling->save($this->validateInput($request));

        return $request->expectsJson()
            ? response()->json(['id' => $appointment->ulid, 'status' => $appointment->status], 201)
            : redirect()->route('agenda.index')->with('status', 'Agendamento criado.');
    }

    public function update(Request $request, string $appointment): RedirectResponse|JsonResponse
    {
        Appointment::query()->findOrFail($appointment);
        $record = $this->scheduling->save($this->validateInput($request), $appointment);

        return $request->expectsJson()
            ? response()->json(['id' => $record->ulid, 'status' => $record->status])
            : redirect()->route('agenda.index')->with('status', 'Agendamento atualizado.');
    }

    public function cancel(Request $request, string $appointment): RedirectResponse|JsonResponse
    {
        $record = Appointment::query()->findOrFail($appointment);
        if ($record->status !== 'cancelled') {
            $record->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            event('agenda.appointment.cancelled', [$record->ulid]);
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
                function ($attribute, $value, $fail) {
                    if (!$this->people->contains($value)) {
                        $fail('Pessoa indisponível neste cadastro.');
                    }
                },
            ],
            'resource_id' => [
                'nullable', 'ulid',
                Rule::exists('agenda_resources', 'ulid')
                    ->where('tenant_id', $tenantId)->where('is_active', true),
            ],
        ]);
    }
}
