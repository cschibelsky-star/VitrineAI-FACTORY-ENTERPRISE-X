<?php

namespace Modules\Cadastro\Contracts;

interface PeopleDirectory
{
    /** @return array<int, array{id: string, name: string}> */
    public function options(): array;

    public function contains(string $id): bool;
}
