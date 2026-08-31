<?php

namespace Modules\Cadastro\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Cadastro\Domain\Contact;
use Modules\Cadastro\Domain\Person;

class PublicRegistrationController
{
    public function create(): View
    {
        return view('cadastro::public-registration');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'preferred_name' => ['nullable', 'string', 'max:120'],
            'document' => ['nullable', 'string', 'max:40'],
            'birth_date' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:200'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $person = Person::create([
            'full_name' => $validated['name'],
            'preferred_name' => $validated['preferred_name'] ?? null,
            'document' => $validated['document'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'metadata' => ['source' => 'public_registration'],
            'is_active' => true,
        ]);

        foreach (['email', 'phone'] as $type) {
            if (!empty($validated[$type])) {
                Contact::create([
                    'person_id' => $person->ulid,
                    'type' => $type,
                    'value' => $validated[$type],
                    'is_primary' => true,
                ]);
            }
        }

        return redirect()
            ->route('cadastro.public.create')
            ->with('status', 'Cadastro recebido com sucesso.');
    }
}
