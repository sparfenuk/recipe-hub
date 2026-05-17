<?php

namespace App\Models;

use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Ingredient extends Model implements AuditableContract, HasMedia
{
    /** @use HasFactory<IngredientFactory> */
    use Auditable, HasFactory, HasTranslations, InteractsWithMedia, Searchable;

    /** @var array<int, string> */
    public array $translatable = ['name'];

    protected $fillable = [
        'slug',
        'name',
        'category_id',
        'default_unit_id',
        'density_g_per_ml',
        'piece_weight_g',
        'kcal_per_100g',
        'protein_g',
        'fat_g',
        'saturated_fat_g',
        'carbs_g',
        'sugar_g',
        'fiber_g',
        'sodium_mg',
        'source',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'density_g_per_ml' => 'decimal:4',
            'piece_weight_g' => 'decimal:2',
            'kcal_per_100g' => 'decimal:2',
            'protein_g' => 'decimal:2',
            'fat_g' => 'decimal:2',
            'saturated_fat_g' => 'decimal:2',
            'carbs_g' => 'decimal:2',
            'sugar_g' => 'decimal:2',
            'fiber_g' => 'decimal:2',
            'sodium_mg' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->width(200)
            ->height(200)
            ->sharpen(10);

        $this->addMediaConversion('card')
            ->width(600)
            ->height(400)
            ->sharpen(10);

        $this->addMediaConversion('full')
            ->width(1600)
            ->sharpen(10);
    }

    /** @return BelongsTo<IngredientCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(IngredientCategory::class, 'category_id');
    }

    /** @return BelongsTo<Unit, $this> */
    public function defaultUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'default_unit_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<IngredientAlias, $this> */
    public function aliases(): HasMany
    {
        return $this->hasMany(IngredientAlias::class);
    }

    /** @return BelongsToMany<Allergen, $this> */
    public function allergens(): BelongsToMany
    {
        return $this->belongsToMany(Allergen::class, 'ingredient_allergen');
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'ingredient_tag');
    }

    /** @return BelongsToMany<Recipe, $this> */
    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_ingredients');
    }

    /** @param  Builder<static>  $query
     *  @return Builder<static> */
    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with('aliases');
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_active;
    }

    /** @return array<string, mixed> */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name_en' => $this->getTranslation('name', 'en', false),
            'name_uk' => $this->getTranslation('name', 'uk', false),
            'aliases' => $this->aliases->pluck('alias')->implode(', '),
        ];
    }
}
