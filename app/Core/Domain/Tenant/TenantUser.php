<?php

namespace App\Core\Domain\Tenant;

use App\Core\Domain\Auth\Role;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TenantUser extends Model
{
    use HasUlids, BelongsToTenant;

    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'ulid');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ulid');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'tenant_user_roles',
            'tenant_user_id',
            'role_id',
            'ulid',
            'ulid'
        );
    }
}
