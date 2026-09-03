<?php

namespace Modules\Atendimento\Domain;

use App\Core\Domain\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ServiceCase extends Model
{
    use HasUlids, BelongsToTenant;

    protected $table = 'atendimento_cases';
    protected $guarded = ['tenant_id'];
    protected $casts = ['opened_at' => 'datetime', 'closed_at' => 'datetime', 'metadata' => 'array'];
}
