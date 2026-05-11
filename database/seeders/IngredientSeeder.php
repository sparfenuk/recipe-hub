<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('ingredients:import-usda', ['--enrich' => true]);

        $this->command->getOutput()->writeln(Artisan::output());
    }
}
