<?php

namespace App\Models;

use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Recipe extends Model implements AuditableContract, HasMedia
{
    /** @use HasFactory<RecipeFactory> */
    use Auditable, HasFactory, HasTranslations, InteractsWithMedia, Searchable, SoftDeletes;

    /** @var array<int, string> */
    public array $translatable = ['title', 'summary', 'description'];

    protected $fillable = [
        'slug',
        'title',
        'summary',
        'description',
        'servings',
        'prep_time_min',
        'cook_time_min',
        'total_time_min',
        'difficulty',
        'category_id',
        'cuisine_id',
        'author_id',
        'status',
        'is_featured',
        'total_kcal',
        'total_protein_g',
        'total_fat_g',
        'total_carbs_g',
        'total_fiber_g',
        'kcal_per_serving',
        'protein_per_serving_g',
        'fat_per_serving_g',
        'carbs_per_serving_g',
        'fiber_per_serving_g',
        'ref_kcal_per_serving',
        'ref_protein_per_serving_g',
        'ref_fat_per_serving_g',
        'ref_carbs_per_serving_g',
        'nutrition_cached_at',
        'published_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'servings' => 'integer',
            'prep_time_min' => 'integer',
            'cook_time_min' => 'integer',
            'total_time_min' => 'integer',
            'is_featured' => 'boolean',
            'total_kcal' => 'decimal:2',
            'total_protein_g' => 'decimal:2',
            'total_fat_g' => 'decimal:2',
            'total_carbs_g' => 'decimal:2',
            'total_fiber_g' => 'decimal:2',
            'kcal_per_serving' => 'decimal:2',
            'protein_per_serving_g' => 'decimal:2',
            'fat_per_serving_g' => 'decimal:2',
            'carbs_per_serving_g' => 'decimal:2',
            'fiber_per_serving_g' => 'decimal:2',
            'ref_kcal_per_serving' => 'decimal:2',
            'ref_protein_per_serving_g' => 'decimal:2',
            'ref_fat_per_serving_g' => 'decimal:2',
            'ref_carbs_per_serving_g' => 'decimal:2',
            'nutrition_cached_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Per-serving nutrition shown to readers. Prefers the source cookbook
     * reference (set at seed time from recipes.json) when available, falling
     * back to the ingredient-computed cache, and finally to total / servings
     * for partially-populated rows in tests. NutritionCalculator still owns
     * `kcal_per_serving` etc.; this accessor is what UI and JSON-LD read.
     */
    public function getDisplayKcalPerServingAttribute(): ?float
    {
        return $this->resolvePerServing('ref_kcal_per_serving', 'kcal_per_serving', 'total_kcal');
    }

    public function getDisplayProteinPerServingGAttribute(): ?float
    {
        return $this->resolvePerServing('ref_protein_per_serving_g', 'protein_per_serving_g', 'total_protein_g');
    }

    public function getDisplayFatPerServingGAttribute(): ?float
    {
        return $this->resolvePerServing('ref_fat_per_serving_g', 'fat_per_serving_g', 'total_fat_g');
    }

    public function getDisplayCarbsPerServingGAttribute(): ?float
    {
        return $this->resolvePerServing('ref_carbs_per_serving_g', 'carbs_per_serving_g', 'total_carbs_g');
    }

    /**
     * Whole-recipe kcal as it should appear in UI (placeholders, calculator labels).
     * Uses the cookbook reference when seeded, otherwise the ingredient-computed total cache.
     */
    public function getDisplayTotalKcalAttribute(): float
    {
        if ($this->ref_kcal_per_serving !== null) {
            return (float) $this->ref_kcal_per_serving * max((int) $this->servings, 1);
        }

        return (float) ($this->total_kcal ?? 0);
    }

    /**
     * Walks ref → per-serving cache → (total / servings). The per-serving + total columns
     * default to 0 in the schema, so treat 0 as "not cached yet" and keep walking.
     */
    private function resolvePerServing(string $refCol, string $perServingCol, string $totalCol): ?float
    {
        if ($this->{$refCol} !== null) {
            return (float) $this->{$refCol};
        }
        $perServing = $this->{$perServingCol};
        if ($perServing !== null && (float) $perServing > 0) {
            return (float) $perServing;
        }
        $total = $this->{$totalCol};
        if ($total !== null && (float) $total > 0) {
            return (float) $total / max((int) $this->servings, 1);
        }

        return null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile();
        $this->addMediaCollection('gallery');
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

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Cuisine, $this> */
    public function cuisine(): BelongsTo
    {
        return $this->belongsTo(Cuisine::class);
    }

    /** @return HasMany<RecipeIngredient, $this> */
    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('position');
    }

    /** @return HasMany<RecipeStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class)->orderBy('position');
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'recipe_tag');
    }

    /** @return BelongsToMany<User, $this> */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withPivot('created_at');
    }

    /** @param  Builder<static>  $query
     *  @return Builder<static> */
    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with('recipeIngredients.ingredient');
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published';
    }

    /** @return array<string, mixed> */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title_en' => $this->getTranslation('title', 'en', false),
            'title_uk' => $this->getTranslation('title', 'uk', false),
            'summary_en' => $this->getTranslation('summary', 'en', false),
            'summary_uk' => $this->getTranslation('summary', 'uk', false),
            'description_en' => strip_tags((string) $this->getTranslation('description', 'en', false)),
            'description_uk' => strip_tags((string) $this->getTranslation('description', 'uk', false)),
            'ingredient_names_en' => $this->recipeIngredients
                ->map(fn (RecipeIngredient $ri) => $ri->ingredient?->getTranslation('name', 'en', false))
                ->filter()
                ->implode(', '),
            'ingredient_names_uk' => $this->recipeIngredients
                ->map(fn (RecipeIngredient $ri) => $ri->ingredient?->getTranslation('name', 'uk', false))
                ->filter()
                ->implode(', '),
        ];
    }
}
