<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(UnitSeeder::class);
        $this->call(IngredientCategorySeeder::class);
        $this->call(CuisineSeeder::class);
        $this->call(TagSeeder::class);
        $this->call(AllergenSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(IngredientSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $admin->assignRole('admin');

        $this->call(RecipeSeeder::class);

        Artisan::call('ingredients:apply-overrides');
        $this->command->getOutput()->writeln(Artisan::output());
    }
}
