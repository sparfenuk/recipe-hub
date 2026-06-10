<?php

namespace App\Models;

use App\Enums\TagType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Translatable\HasTranslations;

/**
 * @property TagType $type
 */
class Tag extends Model implements AuditableContract
{
    use Auditable, HasTranslations;

    public $timestamps = false;

    protected $fillable = [
        'slug',
        'name',
        'type',
    ];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => TagType::class,
        ];
    }

    public function isDiet(): bool
    {
        return $this->type === TagType::Diet;
    }

    public function isCuisine(): bool
    {
        return $this->type === TagType::Cuisine;
    }

    public function isMisc(): bool
    {
        return $this->type === TagType::Misc;
    }

    /** @return BelongsToMany<Recipe, $this> */
    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_tag');
    }
}
