<?php

namespace Modules\Cadastro\Domain;

use App\Core\Domain\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasUlids;
    use BelongsToTenant;

    protected $table = 'cadastro_people';
    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['tenant_id'];

    protected $casts = [
        'birth_date' => 'date',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
