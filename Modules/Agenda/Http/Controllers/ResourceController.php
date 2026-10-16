<?php

namespace Modules\Agenda\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Agenda\Domain\Availability;
use Modules\Agenda\Domain\Resource;
use App\Core\Domain\Tenant\TenantContext;

class ResourceController
{
    public function index(Request $request)
    {
        return response()->json(['resources' => Resource::query()->orderBy('name')->get(),
            'availability' => Availability::query()->orderBy('weekday')->orderBy('starts_at')->get()]);
    }

    public function store(Request $request)
    {
        $record = Resource::create($this->input($request));
        return $this->respond($request, $record, 201);
    }

    public function update(Request $request, string $resource)
    {
        return DB::transaction(function () use ($request, $resource) {
            $record = Resource::query()->lockForUpdate()->findOrFail($resource);
            $record->update($this->input($request));
            return $this->respond($request, $record);
        });
    }

    public function availability(Request $request, ?string $availability = null)
    {
        $data = $request->validate([
            'resource_id' => ['required', 'ulid', Rule::exists('agenda_resources', 'ulid')
                ->where('tenant_id', TenantContext::require())->where('is_active', true)],
            'weekday' => ['required', 'integer', 'between:0,6'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ]);
        $data['starts_at'] .= ':00';
        $data['ends_at'] .= ':00';
        return DB::transaction(function () use ($request, $availability, $data) {
            Resource::query()->lockForUpdate()->findOrFail($data['resource_id']);
            $record = $availability ? Availability::query()->findOrFail($availability) : new Availability();
            $overlap = Availability::query()->where('resource_id', $data['resource_id'])
                ->where('weekday', $data['weekday'])->where('starts_at', '<', $data['ends_at'])
                ->where('ends_at', '>', $data['starts_at']);
            if ($availability) {
                $overlap->where('ulid', '!=', $availability);
            }
            abort_if($overlap->exists(), 422, 'Disponibilidade sobreposta.');
            $record->fill($data)->save();
            return $this->respond($request, $record, $availability ? 200 : 201);
        });
    }

    public function destroyAvailability(Request $request, string $availability)
    {
        return DB::transaction(function () use ($request, $availability) {
            $record = Availability::query()->findOrFail($availability);
            Resource::query()->lockForUpdate()->findOrFail($record->resource_id);
            $record->delete();
            return $this->respond($request, ['deleted' => true]);
        });
    }

    private function input(Request $request): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:180'],
            'type' => ['required', 'string', 'max:60'], 'is_active' => ['sometimes', 'boolean']]);
    }

    private function respond(Request $request, $data, int $status = 200)
    {
        return $request->expectsJson() ? response()->json($data, $status)
            : redirect()->route('agenda.index')->with('status', 'Recurso/disponibilidade atualizado.');
    }
}
