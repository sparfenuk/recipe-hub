<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['slug' => 'vegetarian', 'name' => 'Vegetarian', 'type' => 'diet'],
            ['slug' => 'vegan', 'name' => 'Vegan', 'type' => 'diet'],
            ['slug' => 'keto', 'name' => 'Keto', 'type' => 'diet'],
            ['slug' => 'gluten-free', 'name' => 'Gluten-Free', 'type' => 'diet'],
            ['slug' => 'halal', 'name' => 'Halal', 'type' => 'diet'],
            ['slug' => 'kosher-friendly', 'name' => 'Kosher-Friendly', 'type' => 'diet'],
            ['slug' => 'dairy-free', 'name' => 'Dairy-Free', 'type' => 'diet'],
            ['slug' => 'low-carb', 'name' => 'Low-Carb', 'type' => 'diet'],
            ['slug' => 'paleo', 'name' => 'Paleo', 'type' => 'diet'],
            ['slug' => 'whole30', 'name' => 'Whole30', 'type' => 'diet'],

            ['slug' => 'quick', 'name' => 'Quick', 'type' => 'misc'],
            ['slug' => 'budget-friendly', 'name' => 'Budget-Friendly', 'type' => 'misc'],
            ['slug' => 'one-pot', 'name' => 'One-Pot', 'type' => 'misc'],
            ['slug' => 'no-bake', 'name' => 'No-Bake', 'type' => 'misc'],
            ['slug' => 'meal-prep', 'name' => 'Meal Prep', 'type' => 'misc'],
            ['slug' => 'comfort-food', 'name' => 'Comfort Food', 'type' => 'misc'],
            ['slug' => 'high-protein', 'name' => 'High-Protein', 'type' => 'misc'],
            ['slug' => 'kid-friendly', 'name' => 'Kid-Friendly', 'type' => 'misc'],
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(
                ['slug' => $tag['slug']],
                $tag,
            );
        }
    }
}
