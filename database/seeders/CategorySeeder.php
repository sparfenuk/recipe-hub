<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'breakfast', 'name' => ['en' => 'Breakfast', 'uk' => 'Сніданок']],
            ['slug' => 'lunch', 'name' => ['en' => 'Lunch', 'uk' => 'Обід']],
            ['slug' => 'dinner', 'name' => ['en' => 'Dinner', 'uk' => 'Вечеря']],
            ['slug' => 'appetizers', 'name' => ['en' => 'Appetizers', 'uk' => 'Закуски']],
            ['slug' => 'soups', 'name' => ['en' => 'Soups', 'uk' => 'Супи']],
            ['slug' => 'salads', 'name' => ['en' => 'Salads', 'uk' => 'Салати']],
            ['slug' => 'main-courses', 'name' => ['en' => 'Main Courses', 'uk' => 'Основні страви']],
            ['slug' => 'side-dishes', 'name' => ['en' => 'Side Dishes', 'uk' => 'Гарніри']],
            ['slug' => 'desserts', 'name' => ['en' => 'Desserts', 'uk' => 'Десерти']],
            ['slug' => 'snacks', 'name' => ['en' => 'Snacks', 'uk' => 'Снеки']],
            ['slug' => 'beverages', 'name' => ['en' => 'Beverages', 'uk' => 'Напої']],
            ['slug' => 'sauces-dressings', 'name' => ['en' => 'Sauces & Dressings', 'uk' => 'Соуси та заправки']],
            ['slug' => 'baked-goods', 'name' => ['en' => 'Baked Goods', 'uk' => 'Випічка']],
            ['slug' => 'preserves', 'name' => ['en' => 'Preserves & Pickles', 'uk' => 'Консервація']],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
