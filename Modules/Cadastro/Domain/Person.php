<?php

namespace Modules\Cadastro\Domain;

use App\Core\Domain\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'person_id', 'ulid');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'cadastro_person_tag', 'person_id', 'tag_id')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'cadastro_guardian_relations', 'person_id', 'guardian_person_id')
            ->withPivot(['ulid', 'tenant_id', 'relationship', 'is_primary', 'can_authorize', 'notes'])
            ->withTimestamps();
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'cadastro_guardian_relations', 'guardian_person_id', 'person_id')
            ->withPivot(['ulid', 'tenant_id', 'relationship', 'is_primary', 'can_authorize', 'notes'])
            ->withTimestamps();
    }
}
