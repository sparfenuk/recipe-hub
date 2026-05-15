<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Jobs\RecalculateRecipeNutrition;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\IngredientAlias;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class RecipeSeeder extends Seeder
{
    /** Override the source paths for tests. Null = production defaults. */
    public static ?string $dataPathOverride = null;

    public static ?string $imagesRootOverride = null;

    /**
     * When true, recipes whose slug already exists are force-deleted and re-created from the
     * source JSON. Wipes admin edits, attached media, ingredients, and steps for those slugs.
     * Defaults to false so production / CI runs stay idempotent.
     */
    public static bool $forceOverwrite = false;

    /** Recipe-card units in the source JSON mapped to seeded Unit.code. */
    private const UNIT_MAP = [
        'г' => 'g',
        'мл' => 'ml',
        'шт' => 'piece',
        'зубчик' => 'piece',
        'зубчики' => 'piece',
        'ст. л.' => 'tbsp',
        'ст. л' => 'tbsp',
        'ч. л.' => 'tsp',
        'ч. л' => 'tsp',
        // "to taste" / decorative / optional — collapsed to `taste`, item marked optional
        'за смаком' => 'taste',
        'до смаку' => 'taste',
        'на смак' => 'taste',
        'для смаку' => 'taste',
        'для подачі' => 'taste',
        'для прикраси' => 'taste',
        'за бажанням' => 'taste',
        'кілька крапель' => 'taste',
    ];

    /** Source JSON `category` values mapped to existing CategorySeeder slugs (where possible). */
    private const CATEGORY_MAP = [
        'breakfast' => 'breakfast',
        'lunch' => 'lunch',
        'dinner' => 'dinner',
        'dessert' => 'desserts',
        'snack' => 'snacks',
        'sauce' => 'sauces-dressings',
        'smoothie' => 'smoothies',
        'ice_cream' => 'ice-cream',
        'secret' => 'secret',
    ];

    /** @var array<int, string> */
    private array $unmatchedIngredientsLog = [];

    public function run(): void
    {
        $author = User::role('admin')->orderBy('id')->first();

        if (! $author) {
            throw new RuntimeException(
                'RecipeSeeder requires at least one admin user. Run RoleSeeder + DatabaseSeeder admin block first.',
            );
        }

        $dataPath = self::$dataPathOverride ?? database_path('seeders/data/recipes.json');

        if (! File::exists($dataPath)) {
            throw new RuntimeException("recipes.json not found at {$dataPath}");
        }

        /** @var array<int, array<string, mixed>> $rows */
        $rows = json_decode((string) File::get($dataPath), true, flags: JSON_THROW_ON_ERROR);

        $imagesRoot = self::$imagesRootOverride ?? base_path('pdf_pages');
        $created = 0;
        $skipped = 0;
        $missingImages = 0;

        foreach ($rows as $row) {
            $titleEn = (string) $row['title_en'];
            $slug = Str::slug($titleEn);

            if (Recipe::withTrashed()->where('slug', $slug)->exists()) {
                if (self::$forceOverwrite) {
                    Recipe::withTrashed()->where('slug', $slug)->get()->each->forceDelete();
                } else {
                    // Recipe already exists, but the source JSON may carry bilingual ingredient names
                    // that weren't backfilled on the original seed run (USDA ingredients ship EN-only).
                    // Walk the ingredient list so resolveIngredient() can opportunistically enrich them.
                    foreach ($row['ingredients'] as $ingredientRow) {
                        $this->resolveIngredient(
                            trim((string) $ingredientRow['name_en']),
                            trim((string) $ingredientRow['name_uk']),
                        );
                    }

                    // Backfill reference nutrition for already-seeded recipes that pre-date the ref_*
                    // columns. Idempotent — only writes when the column is null.
                    $existing = Recipe::withTrashed()->where('slug', $slug)->first();
                    if ($existing !== null) {
                        $this->backfillReferenceNutrition($existing, $row);
                    }

                    $skipped++;

                    continue;
                }
            }

            $category = $this->resolveCategory((string) $row['category']);

            [$prep, $cook] = $this->deriveTimes($row['steps_uk'], $row['steps_en']);

            try {
                /** @var Recipe $recipe */
                $recipe = DB::transaction(function () use ($row, $slug, $titleEn, $category, $author, $prep, $cook, $imagesRoot, &$missingImages): Recipe {
                    $recipe = Recipe::withoutEvents(fn () => Recipe::create([
                        'slug' => $slug,
                        'title' => ['uk' => (string) $row['title_uk'], 'en' => $titleEn],
                        'summary' => ['uk' => '', 'en' => ''],
                        'description' => ['uk' => '', 'en' => ''],
                        'servings' => max(1, (int) ($row['servings'] ?? 1)),
                        'prep_time_min' => $prep,
                        'cook_time_min' => $cook,
                        'total_time_min' => $prep + $cook,
                        'difficulty' => 'easy',
                        'category_id' => $category->id,
                        'author_id' => $author->id,
                        'status' => 'published',
                        'published_at' => now(),
                        // Reference nutrition from the source cookbook (per-serving). These override
                        // the ingredient-computed cache for display so on-page values match the book.
                        'ref_kcal_per_serving' => isset($row['calories']) ? (float) $row['calories'] : null,
                        'ref_protein_per_serving_g' => isset($row['protein']) ? (float) $row['protein'] : null,
                        'ref_fat_per_serving_g' => isset($row['fat']) ? (float) $row['fat'] : null,
                        'ref_carbs_per_serving_g' => isset($row['carbs']) ? (float) $row['carbs'] : null,
                    ]));

                    $this->attachIngredients($recipe, $row['ingredients']);
                    $this->attachSteps($recipe, $row['steps_uk'], $row['steps_en']);

                    $imagePath = $imagesRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, (string) $row['image']);

                    if (File::exists($imagePath)) {
                        $recipe->addMedia($imagePath)
                            ->preservingOriginal()
                            ->toMediaCollection('hero');
                    } else {
                        $missingImages++;
                    }

                    return $recipe;
                });

                RecalculateRecipeNutrition::dispatchSync($recipe->id);

                $created++;
            } catch (Throwable $e) {
                $this->command?->error(sprintf('RecipeSeeder failed on "%s" (page %d): %s', $titleEn, (int) ($row['page'] ?? 0), $e->getMessage()));

                throw $e;
            }
        }

        if ($this->unmatchedIngredientsLog !== []) {
            $logPath = storage_path('logs/recipe-seed-'.now()->format('Y-m-d').'.log');
            File::put($logPath, implode(PHP_EOL, $this->unmatchedIngredientsLog).PHP_EOL);

            $this->command?->warn(
                'RecipeSeeder: '.count($this->unmatchedIngredientsLog).' ingredient(s) had no catalog match and were created as stubs. See '.$logPath,
            );
        }

        $this->command?->info("RecipeSeeder: created {$created}, skipped {$skipped} (slug existed), missing images {$missingImages}.");

        // Recipe::create runs inside withoutEvents above, so Scout's observer never fires.
        // Bulk-index after the seed so /recipes search works on a fresh seed.
        Recipe::makeAllSearchable();
    }

    private function resolveCategory(string $jsonCategory): Category
    {
        $slug = self::CATEGORY_MAP[$jsonCategory] ?? Str::slug(str_replace('_', '-', $jsonCategory));

        return Category::firstOrCreate(
            ['slug' => $slug],
            ['name' => Str::headline($slug)],
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function backfillReferenceNutrition(Recipe $recipe, array $row): void
    {
        $updates = [];
        $map = [
            'ref_kcal_per_serving' => 'calories',
            'ref_protein_per_serving_g' => 'protein',
            'ref_fat_per_serving_g' => 'fat',
            'ref_carbs_per_serving_g' => 'carbs',
        ];
        foreach ($map as $column => $sourceKey) {
            if ($recipe->{$column} === null && isset($row[$sourceKey])) {
                $updates[$column] = (float) $row[$sourceKey];
            }
        }
        if ($updates !== []) {
            $recipe->forceFill($updates)->saveQuietly();
        }
    }

    /**
     * Heuristically derive prep/cook times from "хв" / "min" mentions in step text.
     * Returns [prep_min, cook_min] tuple.
     *
     * @param  array<int, string>  $stepsUk
     * @param  array<int, string>  $stepsEn
     * @return array{int, int}
     */
    private function deriveTimes(array $stepsUk, array $stepsEn): array
    {
        $total = 0;

        foreach ([...$stepsUk, ...$stepsEn] as $step) {
            if (preg_match_all('/(\d+)\s*(?:-|–|до)?\s*(\d+)?\s*(?:хв|мін|min(?:ute)?s?|hour|год)/iu', (string) $step, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $upper = isset($match[2]) && $match[2] !== '' ? (int) $match[2] : (int) $match[1];
                    $total += $upper;
                }
            }
        }

        $total = max(5, min(180, $total));

        // Split evenly: a third prep, two thirds cook.
        $prep = (int) max(5, round($total / 3));
        $cook = max(0, $total - $prep);

        return [$prep, $cook];
    }

    /** @param  array<int, array<string, mixed>>  $ingredients */
    private function attachIngredients(Recipe $recipe, array $ingredients): void
    {
        $position = 0;

        foreach ($ingredients as $row) {
            $nameEn = trim((string) $row['name_en']);
            $nameUk = trim((string) $row['name_uk']);
            $rawUnit = trim((string) $row['unit']);
            $amount = (float) ($row['amount'] ?? 0);

            $ingredient = $this->resolveIngredient($nameEn, $nameUk);
            [$unit, $isOptionalByUnit, $note] = $this->resolveUnit($rawUnit, $nameUk);

            $isOptional = $isOptionalByUnit
                || $amount <= 0
                || ! $this->canConvertToGrams($unit, $ingredient);

            RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'ingredient_id' => $ingredient->id,
                'position' => ++$position,
                'amount' => $amount,
                'unit_id' => $unit->id,
                'note' => $note ?: null,
                'is_optional' => $isOptional,
            ]);
        }
    }

    /**
     * Whether the given (unit, ingredient) pair has enough data for `UnitConverter::toGrams`
     * to succeed. Rows that would crash the nutrition calculator are seeded as optional so the
     * recipe still gets a partial nutrition cache for the rows that do have data.
     */
    private function canConvertToGrams(Unit $unit, Ingredient $ingredient): bool
    {
        if ($unit->isMass()) {
            return true;
        }

        if ($unit->isVolume()) {
            return $ingredient->density_g_per_ml !== null && (float) $ingredient->density_g_per_ml > 0;
        }

        return $ingredient->piece_weight_g !== null && (float) $ingredient->piece_weight_g > 0;
    }

    private function resolveIngredient(string $nameEn, string $nameUk): Ingredient
    {
        $ingredient = Ingredient::whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) = ?', [mb_strtolower($nameEn)])
            ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.uk"))) = ?', [mb_strtolower($nameUk)])
            ->first();

        if (! $ingredient) {
            $aliasMatch = IngredientAlias::whereRaw('LOWER(alias) = ?', [mb_strtolower($nameEn)])->first();
            $ingredient = $aliasMatch?->ingredient;
        }

        if ($ingredient) {
            $this->backfillIngredientTranslations($ingredient, $nameEn, $nameUk);

            return $ingredient;
        }

        $this->unmatchedIngredientsLog[] = sprintf('stub created: en="%s" uk="%s"', $nameEn, $nameUk);

        $base = Str::slug($nameEn !== '' ? $nameEn : $nameUk);
        $slug = $base !== '' ? $base : 'ingredient-'.Str::random(6);
        $counter = 1;

        while (Ingredient::where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$counter;
        }

        $names = array_filter([
            'en' => $nameEn !== '' ? $nameEn : $nameUk,
            'uk' => $nameUk !== '' ? $nameUk : $nameEn,
        ], fn (string $value): bool => $value !== '');

        /** @var Ingredient $stub */
        $stub = Ingredient::create([
            'slug' => $slug,
            'name' => $names,
            'source' => 'RecipeSeeder fixture (no USDA match)',
            'is_active' => true,
        ]);

        return $stub;
    }

    /**
     * Backfill missing UK / EN translations on an existing ingredient using the recipe source names.
     * USDA-imported ingredients ship with EN only; the bilingual recipe JSON is the only place we
     * have curated UK names for them, so we opportunistically pick them up on every seed pass.
     */
    private function backfillIngredientTranslations(Ingredient $ingredient, string $nameEn, string $nameUk): void
    {
        $dirty = false;

        if ($nameUk !== '' && $ingredient->getTranslation('name', 'uk', false) === '') {
            $ingredient->setTranslation('name', 'uk', $nameUk);
            $dirty = true;
        }

        if ($nameEn !== '' && $ingredient->getTranslation('name', 'en', false) === '') {
            $ingredient->setTranslation('name', 'en', $nameEn);
            $dirty = true;
        }

        if ($dirty) {
            $ingredient->save();
        }
    }

    /**
     * Returns [Unit, isOptional, note]. note carries any leftover descriptor for optional units
     * (e.g. "for serving", "a few drops") so the original intent isn't lost.
     *
     * @return array{Unit, bool, string|null}
     */
    private function resolveUnit(string $rawUnit, string $ingredientNameUk): array
    {
        $code = self::UNIT_MAP[$rawUnit] ?? null;

        if ($code === null) {
            // Anything unrecognised defaults to `taste` so seeding never fails on an unknown abbreviation.
            $code = 'taste';
        }

        /** @var Unit $unit */
        $unit = Unit::where('code', $code)->firstOrFail();

        $isOptional = $code === 'taste';
        $note = null;

        if ($isOptional && ! in_array($rawUnit, ['за смаком', 'до смаку', 'на смак', 'для смаку'], true)) {
            $note = $rawUnit;
        }

        return [$unit, $isOptional, $note];
    }

    /**
     * @param  array<int, string>  $stepsUk
     * @param  array<int, string>  $stepsEn
     */
    private function attachSteps(Recipe $recipe, array $stepsUk, array $stepsEn): void
    {
        $count = max(count($stepsUk), count($stepsEn));

        for ($i = 0; $i < $count; $i++) {
            RecipeStep::create([
                'recipe_id' => $recipe->id,
                'position' => $i + 1,
                'body' => [
                    'uk' => (string) ($stepsUk[$i] ?? ($stepsEn[$i] ?? '')),
                    'en' => (string) ($stepsEn[$i] ?? ($stepsUk[$i] ?? '')),
                ],
            ]);
        }
    }
}
