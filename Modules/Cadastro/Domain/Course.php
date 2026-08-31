<?php

namespace Modules\Cadastro\Domain;

use App\Core\Domain\Tenant\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasUlids;
    use BelongsToTenant;

    protected $table = 'cadastro_courses';
    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['tenant_id'];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function classes(): HasMany
    {
        return $this->hasMany(CourseClass::class, 'course_id', 'ulid');
    }
}
