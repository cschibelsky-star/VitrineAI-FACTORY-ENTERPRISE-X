<?php

namespace Modules\Atendimento\Domain;

use App\Core\Domain\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ServiceNote extends Model
{
    use HasUlids, BelongsToTenant;

    protected $table = 'atendimento_notes';
    protected $guarded = ['tenant_id'];
}
