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

class Recipe extends Model implements AuditableContract, HasMedia
{
    /** @use HasFactory<RecipeFactory> */
    use Auditable, HasFactory, InteractsWithMedia, Searchable, SoftDeletes;

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
            'nutrition_cached_at' => 'datetime',
            'published_at' => 'datetime',
        ];
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
            'title' => $this->title,
            'summary' => $this->summary,
            'description' => strip_tags((string) $this->description),
            'ingredient_names' => $this->recipeIngredients
                ->map(fn (RecipeIngredient $ri) => $ri->ingredient?->name)
                ->filter()
                ->implode(', '),
        ];
    }
}
