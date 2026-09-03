<?php

namespace Modules\Agenda\Domain;

use App\Core\Domain\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasUlids, BelongsToTenant;

    protected $table = 'agenda_resources';
    protected $guarded = ['tenant_id'];
    protected $casts = ['is_active' => 'boolean', 'metadata' => 'array'];
}
