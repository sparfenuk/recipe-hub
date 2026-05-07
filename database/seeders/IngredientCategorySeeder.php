<?php

namespace Database\Seeders;

use App\Models\IngredientCategory;
use Illuminate\Database\Seeder;

class IngredientCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'vegetables', 'name' => 'Vegetables'],
            ['slug' => 'fruits', 'name' => 'Fruits'],
            ['slug' => 'grains-cereals', 'name' => 'Grains & Cereals'],
            ['slug' => 'dairy', 'name' => 'Dairy & Eggs'],
            ['slug' => 'meat', 'name' => 'Meat'],
            ['slug' => 'poultry', 'name' => 'Poultry'],
            ['slug' => 'seafood', 'name' => 'Seafood'],
            ['slug' => 'legumes', 'name' => 'Legumes'],
            ['slug' => 'nuts-seeds', 'name' => 'Nuts & Seeds'],
            ['slug' => 'herbs-spices', 'name' => 'Herbs & Spices'],
            ['slug' => 'oils-fats', 'name' => 'Oils & Fats'],
            ['slug' => 'sweeteners', 'name' => 'Sweeteners'],
            ['slug' => 'condiments-sauces', 'name' => 'Condiments & Sauces'],
            ['slug' => 'beverages', 'name' => 'Beverages'],
            ['slug' => 'baking', 'name' => 'Baking & Leavening'],
            ['slug' => 'other', 'name' => 'Other'],
        ];

        foreach ($categories as $category) {
            IngredientCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
