<?php

namespace Modules\Cadastro\Domain;

use App\Core\Domain\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasUlids;
    use BelongsToTenant;

    protected $table = 'cadastro_tags';
    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['tenant_id'];

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'cadastro_person_tag', 'tag_id', 'person_id');
    }
}
