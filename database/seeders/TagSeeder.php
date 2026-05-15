<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['slug' => 'vegetarian', 'name' => ['en' => 'Vegetarian', 'uk' => 'Вегетаріанське'], 'type' => 'diet'],
            ['slug' => 'vegan', 'name' => ['en' => 'Vegan', 'uk' => 'Веганське'], 'type' => 'diet'],
            ['slug' => 'keto', 'name' => ['en' => 'Keto', 'uk' => 'Кето'], 'type' => 'diet'],
            ['slug' => 'gluten-free', 'name' => ['en' => 'Gluten-Free', 'uk' => 'Без глютену'], 'type' => 'diet'],
            ['slug' => 'dairy-free', 'name' => ['en' => 'Dairy-Free', 'uk' => 'Без молочних продуктів'], 'type' => 'diet'],
            ['slug' => 'low-carb', 'name' => ['en' => 'Low-Carb', 'uk' => 'Низьковуглеводне'], 'type' => 'diet'],
            ['slug' => 'paleo', 'name' => ['en' => 'Paleo', 'uk' => 'Палео'], 'type' => 'diet'],
            ['slug' => 'whole30', 'name' => ['en' => 'Whole30', 'uk' => 'Whole30'], 'type' => 'diet'],

            ['slug' => 'quick', 'name' => ['en' => 'Quick', 'uk' => 'Швидко'], 'type' => 'misc'],
            ['slug' => 'budget-friendly', 'name' => ['en' => 'Budget-Friendly', 'uk' => 'Бюджетно'], 'type' => 'misc'],
            ['slug' => 'one-pot', 'name' => ['en' => 'One-Pot', 'uk' => 'В одному казанку'], 'type' => 'misc'],
            ['slug' => 'no-bake', 'name' => ['en' => 'No-Bake', 'uk' => 'Без випікання'], 'type' => 'misc'],
            ['slug' => 'meal-prep', 'name' => ['en' => 'Meal Prep', 'uk' => 'Заготівля їжі'], 'type' => 'misc'],
            ['slug' => 'comfort-food', 'name' => ['en' => 'Comfort Food', 'uk' => 'Затишна їжа'], 'type' => 'misc'],
            ['slug' => 'high-protein', 'name' => ['en' => 'High-Protein', 'uk' => 'Високобілкове'], 'type' => 'misc'],
            ['slug' => 'kid-friendly', 'name' => ['en' => 'Kid-Friendly', 'uk' => 'Для дітей'], 'type' => 'misc'],
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(
                ['slug' => $tag['slug']],
                $tag,
            );
        }
    }
}
