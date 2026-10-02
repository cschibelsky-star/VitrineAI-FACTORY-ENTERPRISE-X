<?php

namespace Modules\Agenda\Domain;

use App\Core\Domain\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasUlids, BelongsToTenant;

    protected $table = 'agenda_appointments';
    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['tenant_id'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'cancelled_at' => 'datetime', 'metadata' => 'array'];
}
