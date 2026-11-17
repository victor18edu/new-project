<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
#[Fillable([
    'name',
    'slug',
    'description',
    'is_active',
])]
class Segment extends BaseModel
{
    use HasFactory;
    protected $table = 'segments';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'segment_id');
    }
}
