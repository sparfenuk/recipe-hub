<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\RecipeSeeder;
use Illuminate\Console\Command;

class ReseedRecipes extends Command
{
    /** @var string */
    protected $signature = 'recipes:reseed
                            {--force : Force-delete and re-create recipes whose slug already exists (wipes admin edits + media for those slugs)}';

    /** @var string */
    protected $description = 'Re-run RecipeSeeder, optionally force-overwriting existing recipes';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        if ($force && ! $this->confirm('--force will delete every recipe whose slug is in recipes.json (including their media, ingredients, steps, and any admin edits) and re-create them from the JSON. Continue?', false)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        RecipeSeeder::$forceOverwrite = $force;

        try {
            $this->call('db:seed', [
                '--class' => RecipeSeeder::class,
                '--force' => true,
            ]);
        } finally {
            RecipeSeeder::$forceOverwrite = false;
        }

        return self::SUCCESS;
    }
}
