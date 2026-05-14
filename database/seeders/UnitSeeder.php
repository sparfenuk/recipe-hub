<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'g', 'name' => ['en' => 'g', 'uk' => 'г'], 'type' => 'mass', 'to_base_factor' => 1.0],
            ['code' => 'kg', 'name' => ['en' => 'kg', 'uk' => 'кг'], 'type' => 'mass', 'to_base_factor' => 1000.0],
            ['code' => 'mg', 'name' => ['en' => 'mg', 'uk' => 'мг'], 'type' => 'mass', 'to_base_factor' => 0.001],
            ['code' => 'ml', 'name' => ['en' => 'ml', 'uk' => 'мл'], 'type' => 'volume', 'to_base_factor' => 1.0],
            ['code' => 'l', 'name' => ['en' => 'l', 'uk' => 'л'], 'type' => 'volume', 'to_base_factor' => 1000.0],
            ['code' => 'tsp', 'name' => ['en' => 'tsp', 'uk' => 'ч.л.'], 'type' => 'volume', 'to_base_factor' => 4.92892],
            ['code' => 'tbsp', 'name' => ['en' => 'tbsp', 'uk' => 'ст.л.'], 'type' => 'volume', 'to_base_factor' => 14.7868],
            ['code' => 'cup', 'name' => ['en' => 'cup', 'uk' => 'чашка'], 'type' => 'volume', 'to_base_factor' => 236.588],
            ['code' => 'oz', 'name' => ['en' => 'oz', 'uk' => 'унц.'], 'type' => 'mass', 'to_base_factor' => 28.3495],
            ['code' => 'lb', 'name' => ['en' => 'lb', 'uk' => 'фунт'], 'type' => 'mass', 'to_base_factor' => 453.592],
            ['code' => 'piece', 'name' => ['en' => 'pc', 'uk' => 'шт'], 'type' => 'count', 'to_base_factor' => 1.0],
            ['code' => 'taste', 'name' => ['en' => 'to taste', 'uk' => 'за смаком'], 'type' => 'count', 'to_base_factor' => 0.0],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(['code' => $unit['code']], $unit);
        }
    }
}
