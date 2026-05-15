<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RecalculateRecipeNutrition;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApplyIngredientNutritionOverrides extends Command
{
    /** @var string */
    protected $signature = 'ingredients:apply-overrides
                            {--dry-run : Report what would change without writing}
                            {--no-recompute : Skip dispatching RecalculateRecipeNutrition for all recipes}
                            {--force : Re-apply nutrition even when the ingredient already has values (fixes mis-classified rows)}';

    /** @var string */
    protected $description = 'Backfill nutrition / piece weight / density on ingredients that have no USDA match, using the curated overrides JSON';

    /** @var array<string, int> */
    private array $fdcOverrides = [];

    /** @var array<string, array<string, float|int>> */
    private array $directNutrition = [];

    /** @var array<string, float|int> */
    private array $pieceWeights = [];

    /** @var array<string, float|int> */
    private array $densities = [];

    /** @var array<int, Ingredient> */
    private array $usdaByFdcId = [];

    public function handle(): int
    {
        $path = database_path('seeders/data/ingredient-overrides.json');
        if (! file_exists($path)) {
            $this->error("Overrides file not found: {$path}");

            return self::FAILURE;
        }

        /** @var array{fdc_overrides: array<string, int>, direct_nutrition: array<string, array<string, float|int>>, piece_weights: array<string, float|int>, densities: array<string, float|int>} $rules */
        $rules = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $this->fdcOverrides = $rules['fdc_overrides'];
        $this->directNutrition = $rules['direct_nutrition'];
        $this->pieceWeights = $rules['piece_weights'];
        $this->densities = $rules['densities'];

        foreach (Ingredient::where('source', 'like', 'USDA FDC #%')->get() as $u) {
            if (preg_match('/USDA FDC #(\d+)/', (string) $u->source, $m)) {
                $this->usdaByFdcId[(int) $m[1]] = $u;
            }
        }

        $this->info(sprintf(
            'Loaded %d fdc overrides, %d direct nutrition entries, %d piece weights, %d densities. Indexed %d USDA ingredients by FDC ID.',
            count($this->fdcOverrides),
            count($this->directNutrition),
            count($this->pieceWeights),
            count($this->densities),
            count($this->usdaByFdcId),
        ));

        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN — no writes.');
        }

        $force = (bool) $this->option('force');
        if ($force) {
            $this->warn('FORCE mode — ingredients with existing nutrition will be overwritten.');
        }

        $nutritionApplied = $this->applyNutrition($dryRun, $force);
        $pieceDensityApplied = $this->applyPieceAndDensity($dryRun);
        $relaxed = $this->relaxOptional($dryRun);

        $this->newLine();
        $this->table(['Action', 'Count'], [
            ['Ingredients with nutrition backfilled', $nutritionApplied],
            ['Ingredients with piece weight / density set', $pieceDensityApplied],
            ['Recipe-ingredient rows un-marked optional', $relaxed],
        ]);

        if (! $dryRun && ! $this->option('no-recompute')) {
            $ids = Recipe::pluck('id');
            $this->info("Recomputing nutrition for {$ids->count()} recipes...");
            $bar = $this->output->createProgressBar($ids->count());
            foreach ($ids as $id) {
                RecalculateRecipeNutrition::dispatchSync($id);
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();

            $ok = Recipe::where('total_kcal', '>', 0)->count();
            $this->info("Recipes with total_kcal > 0: {$ok} / {$ids->count()}");
        }

        return self::SUCCESS;
    }

    private function applyNutrition(bool $dryRun, bool $force = false): int
    {
        $count = 0;
        // --force re-resolves stubs that already have values (they may have matched the wrong override
        // keyword). USDA-imported rows keep their original values: they're authoritative for that FDC ID.
        $stubs = $force
            ? Ingredient::where('source', 'not like', 'USDA FDC #%')->get()
            : Ingredient::whereNull('kcal_per_100g')->get();

        foreach ($stubs as $stub) {
            $rawEn = (string) $stub->getTranslation('name', 'en', false);
            if ($rawEn === '') {
                continue;
            }
            $normalized = $this->normalize($rawEn);
            $keys = $this->candidateKeys($normalized);

            $update = null;
            foreach ($keys as $key) {
                if (isset($this->fdcOverrides[$key])) {
                    $u = $this->usdaByFdcId[$this->fdcOverrides[$key]] ?? null;
                    if ($u) {
                        $update = [
                            'kcal_per_100g' => $u->kcal_per_100g,
                            'protein_g' => $u->protein_g,
                            'fat_g' => $u->fat_g,
                            'saturated_fat_g' => $u->saturated_fat_g,
                            'carbs_g' => $u->carbs_g,
                            'sugar_g' => $u->sugar_g,
                            'fiber_g' => $u->fiber_g,
                            'sodium_mg' => $u->sodium_mg,
                            'density_g_per_ml' => $u->density_g_per_ml ?? $stub->density_g_per_ml,
                            'piece_weight_g' => $u->piece_weight_g ?? $stub->piece_weight_g,
                        ];
                        break;
                    }
                }
                if (isset($this->directNutrition[$key])) {
                    $n = $this->directNutrition[$key];
                    $update = [
                        'kcal_per_100g' => $n['kcal_per_100g'],
                        'protein_g' => $n['protein_g'],
                        'fat_g' => $n['fat_g'],
                        'saturated_fat_g' => $n['saturated_fat_g'],
                        'carbs_g' => $n['carbs_g'],
                        'sugar_g' => $n['sugar_g'],
                        'fiber_g' => $n['fiber_g'],
                        'sodium_mg' => $n['sodium_mg'],
                    ];
                    break;
                }
            }

            if ($update === null) {
                continue;
            }

            if (! $dryRun) {
                DB::table('ingredients')->where('id', $stub->id)->update($update);
            }
            $count++;
        }

        return $count;
    }

    private function applyPieceAndDensity(bool $dryRun): int
    {
        $count = 0;
        $ingredients = Ingredient::all(['id', 'name', 'piece_weight_g', 'density_g_per_ml']);

        foreach ($ingredients as $ing) {
            $rawEn = (string) $ing->getTranslation('name', 'en', false);
            if ($rawEn === '') {
                continue;
            }
            $normalized = $this->normalize($rawEn);
            $keys = $this->candidateKeys($normalized);

            $update = [];
            foreach ($keys as $key) {
                if (isset($this->pieceWeights[$key]) && $ing->piece_weight_g === null) {
                    $update['piece_weight_g'] = $this->pieceWeights[$key];
                    break;
                }
            }
            foreach ($keys as $key) {
                if (isset($this->densities[$key]) && $ing->density_g_per_ml === null) {
                    $update['density_g_per_ml'] = $this->densities[$key];
                    break;
                }
            }

            if ($update === []) {
                continue;
            }

            if (! $dryRun) {
                DB::table('ingredients')->where('id', $ing->id)->update($update);
            }
            $count++;
        }

        return $count;
    }

    private function relaxOptional(bool $dryRun): int
    {
        $count = 0;
        foreach (RecipeIngredient::with('ingredient', 'unit')->cursor() as $ri) {
            if (! $ri->is_optional || ! $ri->unit || ! $ri->ingredient) {
                continue;
            }
            if ($ri->unit->code === 'taste' || (float) $ri->amount <= 0) {
                continue;
            }

            $ing = $ri->ingredient;
            $canConvert = match (true) {
                $ri->unit->isMass() => true,
                $ri->unit->isVolume() => $ing->density_g_per_ml !== null && (float) $ing->density_g_per_ml > 0,
                default => $ing->piece_weight_g !== null && (float) $ing->piece_weight_g > 0,
            };

            if (! $canConvert) {
                continue;
            }

            if (! $dryRun) {
                DB::table('recipe_ingredients')->where('id', $ri->id)->update(['is_optional' => false]);
            }
            $count++;
        }

        return $count;
    }

    private function normalize(string $name): string
    {
        $n = mb_strtolower(trim($name));
        $n = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $n);
        $n = preg_replace('/\s*\d+(\.\d+)?\s*%\s*/u', ' ', $n);
        $n = str_replace('/', ' or ', $n);
        $n = preg_replace('/[\s,]+$/u', '', $n);

        return trim(preg_replace('/\s+/u', ' ', $n));
    }

    /** @return list<string> */
    private function candidateKeys(string $normalized): array
    {
        $keys = [$normalized];

        foreach (preg_split('/\s+(?:or|and)\s+/u', $normalized) as $part) {
            $part = trim($part);
            if ($part !== '' && $part !== $normalized) {
                $keys[] = $part;
            }
        }
        foreach (preg_split('/\s*,\s*/u', $normalized) as $part) {
            $part = trim($part);
            if ($part !== '' && $part !== $normalized) {
                $keys[] = $part;
            }
        }

        $expanded = [];
        foreach ($keys as $k) {
            $expanded[] = $k;
            $sing = $this->singularize($k);
            if ($sing !== $k) {
                $expanded[] = $sing;
            }
            $words = preg_split('/\s+/u', $k);
            if (count($words) >= 2) {
                $expanded[] = end($words);
                $expanded[] = $words[0];
                $expanded[] = $this->singularize(end($words));
                $expanded[] = $words[count($words) - 2].' '.$words[count($words) - 1];
            }
        }

        usort($expanded, fn ($a, $b) => mb_strlen($b) - mb_strlen($a));

        return array_values(array_unique(array_filter($expanded, fn ($k) => $k !== '')));
    }

    private function singularize(string $w): string
    {
        if (str_ends_with($w, 'ies') && mb_strlen($w) > 4) {
            return mb_substr($w, 0, -3).'y';
        }
        if (str_ends_with($w, 'es') && mb_strlen($w) > 4) {
            return mb_substr($w, 0, -2);
        }
        if (str_ends_with($w, 's') && ! str_ends_with($w, 'ss') && ! str_ends_with($w, 'us')) {
            return rtrim($w, 's');
        }

        return $w;
    }
}
