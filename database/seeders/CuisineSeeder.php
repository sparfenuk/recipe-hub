<?php

namespace Database\Seeders;

use App\Models\Cuisine;
use Illuminate\Database\Seeder;

class CuisineSeeder extends Seeder
{
    public function run(): void
    {
        $cuisines = [
            ['slug' => 'italian', 'name' => ['en' => 'Italian', 'uk' => 'Італійська']],
            ['slug' => 'french', 'name' => ['en' => 'French', 'uk' => 'Французька']],
            ['slug' => 'mexican', 'name' => ['en' => 'Mexican', 'uk' => 'Мексиканська']],
            ['slug' => 'chinese', 'name' => ['en' => 'Chinese', 'uk' => 'Китайська']],
            ['slug' => 'japanese', 'name' => ['en' => 'Japanese', 'uk' => 'Японська']],
            ['slug' => 'indian', 'name' => ['en' => 'Indian', 'uk' => 'Індійська']],
            ['slug' => 'thai', 'name' => ['en' => 'Thai', 'uk' => 'Тайська']],
            ['slug' => 'greek', 'name' => ['en' => 'Greek', 'uk' => 'Грецька']],
            ['slug' => 'spanish', 'name' => ['en' => 'Spanish', 'uk' => 'Іспанська']],
            ['slug' => 'korean', 'name' => ['en' => 'Korean', 'uk' => 'Корейська']],
            ['slug' => 'vietnamese', 'name' => ['en' => 'Vietnamese', 'uk' => 'В’єтнамська']],
            ['slug' => 'turkish', 'name' => ['en' => 'Turkish', 'uk' => 'Турецька']],
            ['slug' => 'moroccan', 'name' => ['en' => 'Moroccan', 'uk' => 'Марокканська']],
            ['slug' => 'lebanese', 'name' => ['en' => 'Lebanese', 'uk' => 'Ліванська']],
            ['slug' => 'ukrainian', 'name' => ['en' => 'Ukrainian', 'uk' => 'Українська']],
            ['slug' => 'american', 'name' => ['en' => 'American', 'uk' => 'Американська']],
            ['slug' => 'british', 'name' => ['en' => 'British', 'uk' => 'Британська']],
            ['slug' => 'ethiopian', 'name' => ['en' => 'Ethiopian', 'uk' => 'Ефіопська']],
            ['slug' => 'caribbean', 'name' => ['en' => 'Caribbean', 'uk' => 'Карибська']],
            ['slug' => 'other', 'name' => ['en' => 'Other', 'uk' => 'Інша']],
        ];

        foreach ($cuisines as $cuisine) {
            Cuisine::updateOrCreate(
                ['slug' => $cuisine['slug']],
                $cuisine,
            );
        }
    }
}
