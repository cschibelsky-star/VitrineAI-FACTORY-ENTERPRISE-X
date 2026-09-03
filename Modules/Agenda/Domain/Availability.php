<?php

namespace Modules\Agenda\Domain;

use App\Core\Domain\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    use HasUlids, BelongsToTenant;

    protected $table = 'agenda_availabilities';
    protected $guarded = ['tenant_id'];
}
