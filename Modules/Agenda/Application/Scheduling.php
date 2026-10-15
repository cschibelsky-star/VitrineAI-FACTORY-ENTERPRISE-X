<?php

namespace Modules\Agenda\Application;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Agenda\Domain\Appointment;
use Modules\Agenda\Domain\Availability;
use Modules\Agenda\Domain\Resource;

final class Scheduling
{
    public function save(array $data, ?string $id = null): Appointment
    {
        return DB::transaction(function () use ($data, $id) {
            // Serialize bookings and availability changes on the same resource.
            $resource = empty($data['resource_id']) ? null : Resource::query()
                ->where('is_active', true)->lockForUpdate()->findOrFail($data['resource_id']);
            $record = $id ? Appointment::query()->lockForUpdate()->findOrFail($id) : new Appointment();
            abort_if($record->exists && $record->status === 'cancelled', 422, 'Agendamento cancelado.');
            if ($resource) {
                $start = Carbon::parse($data['starts_at']);
                $end = Carbon::parse($data['ends_at']);
                $windows = Availability::query()->where('resource_id', $resource->ulid)->get();
                if ($windows->isNotEmpty() && (!$start->isSameDay($end) || !$windows->contains(
                    fn ($window) => (int) $window->weekday === $start->dayOfWeek
                        && $window->starts_at <= $start->format('H:i:s')
                        && $window->ends_at >= $end->format('H:i:s')
                ))) {
                    throw ValidationException::withMessages(['starts_at' => 'Horário fora da disponibilidade do recurso.']);
                }
                $conflicts = Appointment::query()->where('resource_id', $resource->ulid)
                    ->where('status', '!=', 'cancelled')->where('starts_at', '<', $end)
                    ->where('ends_at', '>', $start);
                if ($id) {
                    $conflicts->where('ulid', '!=', $id);
                }
                if ($conflicts->exists()) {
                    throw ValidationException::withMessages(['resource_id' => 'Recurso já reservado neste horário.']);
                }
            }
            $record->fill($data)->save();
            event($id ? 'agenda.appointment.updated' : 'agenda.appointment.created', [$record->ulid]);
            return $record;
        });
    }
}
