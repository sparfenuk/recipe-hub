<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Recipe> */
class RecipeFactory extends Factory
{
    protected $model = Recipe::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);
        $prep = fake()->numberBetween(5, 60);
        $cook = fake()->numberBetween(10, 120);

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'summary' => fake()->sentence(10),
            'description' => fake()->paragraphs(2, true),
            'servings' => fake()->numberBetween(1, 8),
            'prep_time_min' => $prep,
            'cook_time_min' => $cook,
            'total_time_min' => $prep + $cook,
            'difficulty' => fake()->randomElement(['easy', 'medium', 'hard']),
            'author_id' => User::factory(),
            'status' => 'draft',
            'is_featured' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => 'archived',
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn () => [
            'is_featured' => true,
        ]);
    }
}
