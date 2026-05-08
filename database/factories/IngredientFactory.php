<?php

namespace Database\Factories;

use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Ingredient> */
class IngredientFactory extends Factory
{
    protected $model = Ingredient::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name),
            'name' => ucfirst($name),
            'kcal_per_100g' => fake()->randomFloat(2, 0, 900),
            'protein_g' => fake()->randomFloat(2, 0, 50),
            'fat_g' => fake()->randomFloat(2, 0, 100),
            'carbs_g' => fake()->randomFloat(2, 0, 100),
            'fiber_g' => fake()->randomFloat(2, 0, 40),
            'is_active' => true,
        ];
    }
}
