<?php

namespace Database\Seeders;

use App\Models\Allergen;
use Illuminate\Database\Seeder;

class AllergenSeeder extends Seeder
{
    public function run(): void
    {
        $allergens = [
            ['slug' => 'gluten', 'name' => 'Gluten'],
            ['slug' => 'lactose', 'name' => 'Lactose'],
            ['slug' => 'nuts', 'name' => 'Nuts'],
            ['slug' => 'soy', 'name' => 'Soy'],
            ['slug' => 'eggs', 'name' => 'Eggs'],
            ['slug' => 'fish', 'name' => 'Fish'],
            ['slug' => 'shellfish', 'name' => 'Shellfish'],
            ['slug' => 'sesame', 'name' => 'Sesame'],
            ['slug' => 'mustard', 'name' => 'Mustard'],
        ];

        foreach ($allergens as $allergen) {
            Allergen::updateOrCreate(
                ['slug' => $allergen['slug']],
                $allergen,
            );
        }
    }
}
