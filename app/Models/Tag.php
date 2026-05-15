<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Translatable\HasTranslations;

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

    public function isDiet(): bool
    {
        return $this->type === 'diet';
    }

    public function isCuisine(): bool
    {
        return $this->type === 'cuisine';
    }

    public function isMisc(): bool
    {
        return $this->type === 'misc';
    }

    /** @return BelongsToMany<Recipe, $this> */
    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_tag');
    }
}
