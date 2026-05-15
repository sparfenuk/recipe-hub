<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Console\Command;

class AutoTagRecipes extends Command
{
    /** @var string */
    protected $signature = 'recipes:auto-tag
                            {--dry-run : Report tag matches without writing to the database}';

    /** @var string */
    protected $description = 'Auto-apply diet tags to published recipes based on ingredient allergens and name keywords';

    /**
     * @var list<array{tag: string, exclude_allergens?: list<string>, exclude_ingredient_keywords?: list<string>}>
     */
    private array $rules = [];

    public function handle(): int
    {
        $rulesPath = database_path('seeders/data/diet-rules.json');

        if (! file_exists($rulesPath)) {
            $this->error("Rules file not found: {$rulesPath}");

            return self::FAILURE;
        }

        /** @var array{rules: list<array{tag: string, exclude_allergens?: list<string>, exclude_ingredient_keywords?: list<string>}>} $parsed */
        $parsed = json_decode((string) file_get_contents($rulesPath), true);
        $this->rules = $parsed['rules'];

        $tagCache = Tag::where('type', 'diet')->pluck('id', 'slug')->all();

        foreach ($this->rules as $rule) {
            if (! isset($tagCache[$rule['tag']])) {
                $this->warn("Skipping unknown tag slug: {$rule['tag']}");
            }
        }

        $dryRun = (bool) $this->option('dry-run');

        /** @var array<string, int> $stats */
        $stats = [];

        Recipe::where('status', 'published')
            ->with(['recipeIngredients.ingredient.allergens:id,slug'])
            ->chunkById(100, function ($recipes) use ($tagCache, $dryRun, &$stats): void {
                foreach ($recipes as $recipe) {
                    /** @var Recipe $recipe */
                    $allergenSlugs = $this->collectAllergenSlugs($recipe);
                    $ingredientNames = $this->collectIngredientNames($recipe);

                    foreach ($this->rules as $rule) {
                        $tagId = $tagCache[$rule['tag']] ?? null;
                        if ($tagId === null) {
                            continue;
                        }

                        if (! $this->recipeMatches($rule, $allergenSlugs, $ingredientNames)) {
                            continue;
                        }

                        $stats[$rule['tag']] = ($stats[$rule['tag']] ?? 0) + 1;

                        if (! $dryRun) {
                            $recipe->tags()->syncWithoutDetaching([$tagId]);
                        }
                    }
                }
            });

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '').'Auto-tagging complete.');

        $rows = [];
        foreach ($this->rules as $rule) {
            $rows[] = [$rule['tag'], $stats[$rule['tag']] ?? 0];
        }
        $this->table(['Diet tag', 'Recipes matched'], $rows);

        return self::SUCCESS;
    }

    /**
     * @param  array{tag: string, exclude_allergens?: list<string>, exclude_ingredient_keywords?: list<string>}  $rule
     * @param  list<string>  $allergenSlugs
     * @param  list<string>  $ingredientNames
     */
    private function recipeMatches(array $rule, array $allergenSlugs, array $ingredientNames): bool
    {
        $excludeAllergens = $rule['exclude_allergens'] ?? [];
        if ($excludeAllergens !== [] && array_intersect($excludeAllergens, $allergenSlugs) !== []) {
            return false;
        }

        $excludeKeywords = $rule['exclude_ingredient_keywords'] ?? [];
        if ($excludeKeywords !== []) {
            foreach ($ingredientNames as $name) {
                foreach ($excludeKeywords as $keyword) {
                    if ($this->matchKeyword($name, $keyword)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /** @return list<string> */
    private function collectAllergenSlugs(Recipe $recipe): array
    {
        $slugs = [];
        foreach ($recipe->recipeIngredients as $ri) {
            $ingredient = $ri->ingredient;
            if ($ingredient === null) {
                continue;
            }
            foreach ($ingredient->allergens as $allergen) {
                $slugs[$allergen->slug] = true;
            }
        }

        return array_keys($slugs);
    }

    /** @return list<string> */
    private function collectIngredientNames(Recipe $recipe): array
    {
        $names = [];
        foreach ($recipe->recipeIngredients as $ri) {
            $ingredient = $ri->ingredient;
            if ($ingredient === null) {
                continue;
            }
            $name = $ingredient->getTranslation('name', 'en', false);
            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    private function matchKeyword(string $haystack, string $keyword): bool
    {
        $pattern = '/\b'.preg_quote(mb_strtolower($keyword), '/').'\b/u';

        return preg_match($pattern, mb_strtolower($haystack)) === 1;
    }
}
