<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Allergen;
use App\Models\Ingredient;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class EnrichIngredients extends Command
{
    /** @var string */
    protected $signature = 'ingredients:enrich
                            {--dry-run : Report matches without writing to the database}';

    /** @var string */
    protected $description = 'Re-apply allergen keyword rules to all active ingredients';

    /** @var array<string, int> */
    private array $allergenCache = [];

    /** @var list<array{allergen: string, keywords: list<string>}> */
    private array $keywordRules = [];

    /** @var list<array{allergen: string, category_slugs: list<string>}> */
    private array $categoryRules = [];

    public function handle(): int
    {
        $rulesPath = database_path('seeders/data/allergen-rules.json');

        if (! file_exists($rulesPath)) {
            $this->error("Rules file not found: {$rulesPath}");

            return self::FAILURE;
        }

        /** @var array{by_keyword: list<array{allergen: string, keywords: list<string>}>, by_category: list<array{allergen: string, category_slugs: list<string>}>} $rules */
        $rules = json_decode((string) file_get_contents($rulesPath), true);
        $this->keywordRules = $rules['by_keyword'];
        $this->categoryRules = $rules['by_category'];
        $this->allergenCache = Allergen::pluck('id', 'slug')->all();

        $dryRun = (bool) $this->option('dry-run');

        $processed = 0;
        $matchedIngredients = 0;
        /** @var array<int, int> $perAllergen */
        $perAllergen = [];

        Ingredient::where('is_active', true)
            ->with('category:id,slug')
            ->chunkById(500, function (Collection $ingredients) use ($dryRun, &$processed, &$matchedIngredients, &$perAllergen): void {
                foreach ($ingredients as $ingredient) {
                    /** @var Ingredient $ingredient */
                    $processed++;

                    $name = $ingredient->getTranslation('name', 'en', false);
                    if (! is_string($name) || $name === '') {
                        continue;
                    }

                    $category = $ingredient->category;
                    $categorySlug = $category === null ? '' : $category->slug;
                    $allergenIds = $this->matchAllergens($name, $categorySlug);

                    if ($allergenIds === []) {
                        continue;
                    }

                    $matchedIngredients++;
                    foreach ($allergenIds as $aid) {
                        $perAllergen[$aid] = ($perAllergen[$aid] ?? 0) + 1;
                    }

                    if (! $dryRun) {
                        $ingredient->allergens()->syncWithoutDetaching($allergenIds);
                    }
                }
            });

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '')."Processed {$processed} ingredients, matched {$matchedIngredients}.");

        $rows = [];
        foreach ($this->allergenCache as $slug => $aid) {
            $rows[] = [$slug, $perAllergen[$aid] ?? 0];
        }
        $this->table(['Allergen', 'Ingredients tagged this run'], $rows);

        return self::SUCCESS;
    }

    /** @return list<int> */
    private function matchAllergens(string $name, string $categorySlug): array
    {
        $matched = [];

        foreach ($this->keywordRules as $rule) {
            $allergenId = $this->allergenCache[$rule['allergen']] ?? null;
            if ($allergenId === null) {
                continue;
            }

            foreach ($rule['keywords'] as $keyword) {
                if ($this->matchKeyword($name, $keyword)) {
                    $matched[$allergenId] = true;
                    break;
                }
            }
        }

        if ($categorySlug !== '') {
            foreach ($this->categoryRules as $rule) {
                if (in_array($categorySlug, $rule['category_slugs'], true)) {
                    $allergenId = $this->allergenCache[$rule['allergen']] ?? null;
                    if ($allergenId !== null) {
                        $matched[$allergenId] = true;
                    }
                }
            }
        }

        return array_keys($matched);
    }

    private function matchKeyword(string $haystack, string $keyword): bool
    {
        // Word-boundary match to avoid false positives like "egg" matching "Eggplant".
        $pattern = '/\b'.preg_quote(mb_strtolower($keyword), '/').'\b/u';

        return preg_match($pattern, mb_strtolower($haystack)) === 1;
    }
}
