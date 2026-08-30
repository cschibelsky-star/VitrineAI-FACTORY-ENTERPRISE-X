<?php

namespace App\Core\Domain\Auth;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasUlids;

    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id',
            'ulid',
            'ulid'
        );
    }
}
