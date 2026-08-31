<?php

namespace Modules\Cadastro\Http\Controllers;

use App\Core\Domain\Tenant\TenantBranding;
use App\Core\Domain\Tenant\TenantContext;
use Illuminate\View\View;
use Modules\Cadastro\Domain\Person;

class PrintPersonController
{
    public function __invoke(string $person): View
    {
        $record = Person::query()
            ->with(['contacts', 'guardians.contacts'])
            ->where('ulid', $person)
            ->firstOrFail();

        $branding = TenantBranding::query()
            ->where('tenant_id', TenantContext::require())
            ->first();

        return view('cadastro::print-person', [
            'person' => $record,
            'branding' => $branding,
        ]);
    }
}
