<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class IngredientSeeder extends Seeder
{
    /** Override the curated USDA CSV path for tests. Null = production default. */
    public static ?string $csvPathOverride = null;

    public function run(): void
    {
        $params = ['--enrich' => true];
        if (self::$csvPathOverride !== null) {
            $params['path'] = self::$csvPathOverride;
        }

        Artisan::call('ingredients:import-usda', $params);

        $this->command->getOutput()->writeln(Artisan::output());
    }
}
