<?php

namespace App\Core\Domain\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantBranding extends Model
{
    use HasUlids;

    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'ulid');
    }
}
