<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Translatable\HasTranslations;

class Cuisine extends Model implements AuditableContract
{
    use Auditable, HasTranslations;

    public $timestamps = false;

    protected $fillable = [
        'slug',
        'name',
    ];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    /** @return HasMany<Recipe, $this> */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }
}
