<?php

namespace Database\Seeders;

use App\Models\Allergen;
use Illuminate\Database\Seeder;

class AllergenSeeder extends Seeder
{
    public function run(): void
    {
        $allergens = [
            ['slug' => 'gluten', 'name' => ['en' => 'Gluten', 'uk' => 'Глютен']],
            ['slug' => 'lactose', 'name' => ['en' => 'Lactose', 'uk' => 'Лактоза']],
            ['slug' => 'nuts', 'name' => ['en' => 'Nuts', 'uk' => 'Горіхи']],
            ['slug' => 'soy', 'name' => ['en' => 'Soy', 'uk' => 'Соя']],
            ['slug' => 'eggs', 'name' => ['en' => 'Eggs', 'uk' => 'Яйця']],
            ['slug' => 'fish', 'name' => ['en' => 'Fish', 'uk' => 'Риба']],
            ['slug' => 'shellfish', 'name' => ['en' => 'Shellfish', 'uk' => 'Молюски']],
            ['slug' => 'sesame', 'name' => ['en' => 'Sesame', 'uk' => 'Кунжут']],
            ['slug' => 'mustard', 'name' => ['en' => 'Mustard', 'uk' => 'Гірчиця']],
        ];

        foreach ($allergens as $allergen) {
            Allergen::updateOrCreate(
                ['slug' => $allergen['slug']],
                $allergen,
            );
        }
    }
}
