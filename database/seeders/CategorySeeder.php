<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'breakfast', 'name' => 'Breakfast'],
            ['slug' => 'lunch', 'name' => 'Lunch'],
            ['slug' => 'dinner', 'name' => 'Dinner'],
            ['slug' => 'appetizers', 'name' => 'Appetizers'],
            ['slug' => 'soups', 'name' => 'Soups'],
            ['slug' => 'salads', 'name' => 'Salads'],
            ['slug' => 'main-courses', 'name' => 'Main Courses'],
            ['slug' => 'side-dishes', 'name' => 'Side Dishes'],
            ['slug' => 'desserts', 'name' => 'Desserts'],
            ['slug' => 'snacks', 'name' => 'Snacks'],
            ['slug' => 'beverages', 'name' => 'Beverages'],
            ['slug' => 'sauces-dressings', 'name' => 'Sauces & Dressings'],
            ['slug' => 'baked-goods', 'name' => 'Baked Goods'],
            ['slug' => 'preserves', 'name' => 'Preserves & Pickles'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
