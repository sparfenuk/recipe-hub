<?php

namespace App\Models;

use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    /** @use HasFactory<IngredientFactory> */
    use HasFactory;

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
}
