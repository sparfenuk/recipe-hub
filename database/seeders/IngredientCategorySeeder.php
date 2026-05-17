<?php

namespace Database\Seeders;

use App\Models\IngredientCategory;
use Illuminate\Database\Seeder;

class IngredientCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'vegetables', 'name' => ['en' => 'Vegetables', 'uk' => 'Овочі']],
            ['slug' => 'fruits', 'name' => ['en' => 'Fruits', 'uk' => 'Фрукти']],
            ['slug' => 'grains-cereals', 'name' => ['en' => 'Grains & Cereals', 'uk' => 'Крупи та зернові']],
            ['slug' => 'dairy', 'name' => ['en' => 'Dairy & Eggs', 'uk' => 'Молочні продукти та яйця']],
            ['slug' => 'meat', 'name' => ['en' => 'Meat', 'uk' => 'М’ясо']],
            ['slug' => 'poultry', 'name' => ['en' => 'Poultry', 'uk' => 'Птиця']],
            ['slug' => 'seafood', 'name' => ['en' => 'Seafood', 'uk' => 'Морепродукти']],
            ['slug' => 'legumes', 'name' => ['en' => 'Legumes', 'uk' => 'Бобові']],
            ['slug' => 'nuts-seeds', 'name' => ['en' => 'Nuts & Seeds', 'uk' => 'Горіхи та насіння']],
            ['slug' => 'herbs-spices', 'name' => ['en' => 'Herbs & Spices', 'uk' => 'Трави та спеції']],
            ['slug' => 'oils-fats', 'name' => ['en' => 'Oils & Fats', 'uk' => 'Олії та жири']],
            ['slug' => 'sweeteners', 'name' => ['en' => 'Sweeteners', 'uk' => 'Підсолоджувачі']],
            ['slug' => 'condiments-sauces', 'name' => ['en' => 'Condiments & Sauces', 'uk' => 'Приправи та соуси']],
            ['slug' => 'beverages', 'name' => ['en' => 'Beverages', 'uk' => 'Напої']],
            ['slug' => 'baking', 'name' => ['en' => 'Baking & Leavening', 'uk' => 'Випічка та розпушувачі']],
            ['slug' => 'other', 'name' => ['en' => 'Other', 'uk' => 'Інше']],
        ];

        foreach ($categories as $category) {
            IngredientCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
