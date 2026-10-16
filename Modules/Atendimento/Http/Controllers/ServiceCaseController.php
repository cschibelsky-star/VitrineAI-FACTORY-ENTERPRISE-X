<?php

namespace Modules\Atendimento\Http\Controllers;

use App\Core\Domain\Tenant\TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Atendimento\Domain\ServiceCase;
use Modules\Atendimento\Domain\ServiceNote;
use Modules\Atendimento\Domain\CaseHistory;
use Modules\Cadastro\Contracts\PeopleDirectory;

class ServiceCaseController
{
    public function __construct(private PeopleDirectory $people) {}

    public function index(Request $request)
    {
        $data = $request->validate(['status' => ['nullable', Rule::in(['open', 'in_progress', 'waiting', 'closed'])]]);
        $cases = ServiceCase::query()->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('opened_at')->paginate(20)->withQueryString();
        return $request->expectsJson() ? response()->json($cases) : view('atendimento::index', [
            'cases' => $cases, 'people' => $this->people->options(), 'responsibles' => $this->responsibles(),
        ]);
    }

    public function show(Request $request, string $case)
    {
        $record = ServiceCase::query()->findOrFail($case);
        $data = ['case' => $record, 'notes' => ServiceNote::query()->where('case_id', $case)->oldest()->get(),
            'history' => CaseHistory::query()->where('case_id', $case)->oldest()->get(),
            'responsibles' => $this->responsibles()];
        return $request->expectsJson() ? response()->json($data) : view('atendimento::show', $data);
    }

    public function store(Request $request)
    {
        $data = $this->input($request);
        return DB::transaction(function () use ($request, $data) {
            $case = ServiceCase::create($data + ['status' => 'open', 'opened_at' => now()]);
            $this->history($request, $case, 'created', null, $case->only(['subject', 'person_id', 'status', 'responsible_user_id']));
            event('atendimento.case.created', [$case->ulid]);
            return $this->respond($request, $case, 201);
        });
    }

    public function update(Request $request, string $case)
    {
        ServiceCase::query()->findOrFail($case);
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'status' => ['required', Rule::in(['open', 'in_progress', 'waiting'])],
            'responsible_user_id' => $this->responsibleRules(),
        ]);
        return DB::transaction(function () use ($request, $case, $data) {
            $record = ServiceCase::query()->lockForUpdate()->findOrFail($case);
            abort_if($record->status === 'closed', 422, 'Atendimento encerrado.');
            $before = $record->only(array_keys($data));
            $record->update($data);
            $this->history($request, $record, 'updated', $before, $record->only(array_keys($data)));
            event('atendimento.case.updated', [$record->ulid]);
            return $this->respond($request, $record);
        });
    }

    public function close(Request $request, string $case)
    {
        return DB::transaction(function () use ($request, $case) {
            $record = ServiceCase::query()->lockForUpdate()->findOrFail($case);
            if ($record->status !== 'closed') {
                $before = $record->only(['status']);
                $record->update(['status' => 'closed', 'closed_at' => now()]);
                $this->history($request, $record, 'closed', $before, ['status' => 'closed']);
                event('atendimento.case.closed', [$record->ulid]);
            }
            return $this->respond($request, $record);
        });
    }

    public function note(Request $request, string $case)
    {
        ServiceCase::query()->findOrFail($case);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        return DB::transaction(function () use ($request, $case, $data) {
            $record = ServiceCase::query()->lockForUpdate()->findOrFail($case);
            abort_if($record->status === 'closed', 422, 'Atendimento encerrado.');
            $note = ServiceNote::create($data + ['case_id' => $record->ulid,
                'author_user_id' => $request->user()->getAuthIdentifier()]);
            $this->history($request, $record, 'note_added', null, ['note_id' => $note->ulid]);
            return $request->expectsJson() ? response()->json($note, 201)
                : redirect()->route('atendimento.cases.show', $case)->with('status', 'Nota registrada.');
        });
    }

    private function input(Request $request): array
    {
        return $request->validate([
            'person_id' => ['required', 'ulid', function ($attribute, $value, $fail) {
                if (!$this->people->contains($value)) { $fail('Pessoa indisponível neste cadastro.'); }
            }],
            'subject' => ['required', 'string', 'max:180'],
            'responsible_user_id' => $this->responsibleRules(),
        ]);
    }

    private function responsibleRules(): array
    {
        return ['nullable', 'ulid', function ($attribute, $value, $fail) {
            if (!TenantUser::query()->where('user_id', $value)->where('status', 'active')
                ->whereHas('user', fn ($q) => $q->where('is_active', true))->exists()) {
                $fail('Responsável não pertence à equipe ativa desta empresa.');
            }
        }];
    }

    private function responsibles()
    {
        return TenantUser::query()->where('status', 'active')
            ->whereHas('user', fn ($q) => $q->where('is_active', true))->with('user')->get()
            ->map(fn ($member) => ['id' => $member->user_id, 'name' => $member->user->name]);
    }

    private function history(Request $request, ServiceCase $case, string $action, ?array $before, array $after): void
    {
        CaseHistory::create(['case_id' => $case->ulid, 'actor_user_id' => $request->user()->getAuthIdentifier(),
            'action' => $action, 'before' => $before, 'after' => $after]);
    }

    private function respond(Request $request, ServiceCase $case, int $status = 200)
    {
        return $request->expectsJson() ? response()->json($case, $status)
            : redirect()->route('atendimento.cases.show', $case->ulid)->with('status', 'Atendimento atualizado.');
    }
}
