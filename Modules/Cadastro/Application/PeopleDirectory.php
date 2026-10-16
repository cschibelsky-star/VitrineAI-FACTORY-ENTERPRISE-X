<?php

namespace Modules\Cadastro\Application;

use Modules\Cadastro\Contracts\PeopleDirectory as Contract;
use Modules\Cadastro\Domain\Person;

final class PeopleDirectory implements Contract
{
    public function options(): array
    {
        return Person::query()->where('is_active', true)->orderBy('full_name')
            ->limit(200)->get(['ulid', 'full_name'])
            ->map(fn (Person $person) => ['id' => $person->ulid, 'name' => $person->full_name])->all();
    }

    public function contains(string $id): bool
    {
        return Person::query()->where('is_active', true)->whereKey($id)->exists();
    }
}
