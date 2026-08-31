<?php

namespace Modules\Cadastro\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Cadastro\Domain\Person;

class PersonController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $people = Person::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('full_name', 'like', "%{$search}%")
                        ->orWhere('preferred_name', 'like', "%{$search}%")
                        ->orWhere('document', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        return view('cadastro::index', compact('people', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        Person::create($this->validated($request));

        return redirect()->route('cadastro.index')->with('status', 'Cadastro criado com sucesso.');
    }

    public function update(Request $request, Person $person): RedirectResponse
    {
        $person->update($this->validated($request));

        return redirect()->route('cadastro.index', ['q' => $request->input('q')])
            ->with('status', 'Cadastro atualizado com sucesso.');
    }

    public function destroy(Person $person): RedirectResponse
    {
        $person->delete();

        return redirect()->route('cadastro.index')->with('status', 'Cadastro excluído com sucesso.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:200'],
            'preferred_name' => ['nullable', 'string', 'max:120'],
            'document' => ['nullable', 'string', 'max:40'],
            'birth_date' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:200'],
            'phone' => ['nullable', 'string', 'max:40'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
