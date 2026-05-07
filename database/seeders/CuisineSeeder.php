<?php

namespace Database\Seeders;

use App\Models\Cuisine;
use Illuminate\Database\Seeder;

class CuisineSeeder extends Seeder
{
    public function run(): void
    {
        $cuisines = [
            ['slug' => 'italian', 'name' => 'Italian'],
            ['slug' => 'french', 'name' => 'French'],
            ['slug' => 'mexican', 'name' => 'Mexican'],
            ['slug' => 'chinese', 'name' => 'Chinese'],
            ['slug' => 'japanese', 'name' => 'Japanese'],
            ['slug' => 'indian', 'name' => 'Indian'],
            ['slug' => 'thai', 'name' => 'Thai'],
            ['slug' => 'greek', 'name' => 'Greek'],
            ['slug' => 'spanish', 'name' => 'Spanish'],
            ['slug' => 'korean', 'name' => 'Korean'],
            ['slug' => 'vietnamese', 'name' => 'Vietnamese'],
            ['slug' => 'turkish', 'name' => 'Turkish'],
            ['slug' => 'moroccan', 'name' => 'Moroccan'],
            ['slug' => 'lebanese', 'name' => 'Lebanese'],
            ['slug' => 'ukrainian', 'name' => 'Ukrainian'],
            ['slug' => 'american', 'name' => 'American'],
            ['slug' => 'british', 'name' => 'British'],
            ['slug' => 'ethiopian', 'name' => 'Ethiopian'],
            ['slug' => 'caribbean', 'name' => 'Caribbean'],
            ['slug' => 'other', 'name' => 'Other'],
        ];

        foreach ($cuisines as $cuisine) {
            Cuisine::updateOrCreate(
                ['slug' => $cuisine['slug']],
                $cuisine,
            );
        }
    }
}
