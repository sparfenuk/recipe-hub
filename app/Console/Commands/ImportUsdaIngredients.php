<?php

namespace App\Console\Commands;

use App\Models\Allergen;
use App\Models\Ingredient;
use App\Models\IngredientAlias;
use App\Models\IngredientCategory;
use App\Models\Unit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportUsdaIngredients extends Command
{
    /** @var string */
    protected $signature = 'ingredients:import-usda
                            {path? : Path to curated CSV (default: database/seeders/data/usda-curated.csv)}
                            {--dry-run : Show what would be imported without writing to the database}
                            {--chunk=1000 : Number of rows to process per database transaction}
                            {--enrich : Apply enrichment data (densities, allergens, aliases)}';

    /** @var string */
    protected $description = 'Import ingredients from the USDA curated CSV into the database';

    /** @var array<string, int> */
    private array $categoryCache = [];

    /** @var array<string, int> */
    private array $allergenCache = [];

    /** @var array<string, int> */
    private array $unitCache = [];

    /**
     * @var list<array{keywords: list<string>, density_g_per_ml: float, default_unit: string}>
     */
    private array $densityRules = [];

    /**
     * @var list<array{allergen: string, keywords: list<string>}>
     */
    private array $allergenKeywordRules = [];

    /**
     * @var list<array{allergen: string, category_slugs: list<string>}>
     */
    private array $allergenCategoryRules = [];

    /**
     * @var list<array{keywords: list<string>, aliases: list<string>}>
     */
    private array $aliasRules = [];

    private bool $enrich = false;

    public function handle(): int
    {
        /** @var string $path */
        $path = $this->argument('path') ?? database_path('seeders/data/usda-curated.csv');

        if (! file_exists($path)) {
            $this->error("CSV file not found: {$path}");

            return self::FAILURE;
        }

        $this->enrich = (bool) $this->option('enrich');

        if ($this->enrich && ! $this->loadEnrichmentData()) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $logChannel = $this->createLogChannel();

        if ($dryRun) {
            $this->info('DRY RUN — no data will be written.');
        }

        $this->loadCategoryCache();

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error("Cannot open CSV: {$path}");

            return self::FAILURE;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            $this->error('CSV file is empty or unreadable.');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $enriched = 0;
        $chunk = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count($row) !== count($header)) {
                $this->logError($logChannel, $rowNum, 'Column count mismatch');
                $errors++;

                continue;
            }

            /** @var array<string, string> $data */
            $data = array_combine($header, $row);
            $chunk[] = ['row' => $rowNum, 'data' => $data];

            if (count($chunk) >= $chunkSize) {
                $result = $this->processChunk($chunk, $dryRun, $logChannel);
                $created += $result['created'];
                $updated += $result['updated'];
                $skipped += $result['skipped'];
                $errors += $result['errors'];
                $enriched += $result['enriched'];
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            $result = $this->processChunk($chunk, $dryRun, $logChannel);
            $created += $result['created'];
            $updated += $result['updated'];
            $skipped += $result['skipped'];
            $errors += $result['errors'];
            $enriched += $result['enriched'];
        }

        fclose($handle);

        $this->newLine();
        $this->info('Import complete.');
        $headers = ['Created', 'Updated', 'Skipped', 'Errors'];
        $row = [$created, $updated, $skipped, $errors];

        if ($this->enrich) {
            $headers[] = 'Enriched';
            $row[] = $enriched;
        }

        $this->table($headers, [$row]);

        if ($errors > 0) {
            $this->warn('See log for error details: storage/logs/usda-import-'.date('Y-m-d').'.log');
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<array{row: int, data: array<string, string>}>  $chunk
     * @return array{created: int, updated: int, skipped: int, errors: int, enriched: int}
     */
    private function processChunk(array $chunk, bool $dryRun, string $logChannel): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $enriched = 0;

        $callback = function () use ($chunk, $dryRun, $logChannel, &$created, &$updated, &$errors, &$enriched): void {
            foreach ($chunk as $item) {
                $rowNum = $item['row'];
                $data = $item['data'];

                $fdcId = trim($data['fdc_id'] ?? '');
                $name = trim($data['name'] ?? '');

                if ($fdcId === '' || $name === '') {
                    $this->logError($logChannel, $rowNum, 'Missing fdc_id or name');
                    $errors++;

                    continue;
                }

                $categorySlug = trim($data['category_slug'] ?? '');
                $categoryId = $this->categoryCache[$categorySlug] ?? null;

                if ($categorySlug !== '' && $categoryId === null) {
                    $this->logError($logChannel, $rowNum, "Unknown category slug: {$categorySlug}");
                    $errors++;

                    continue;
                }

                $source = "USDA FDC #{$fdcId}";
                $slug = $this->uniqueSlug($name, $source);

                $attributes = [
                    'slug' => $slug,
                    'name' => $name,
                    'category_id' => $categoryId,
                    'kcal_per_100g' => $this->nullableDecimal($data['kcal_per_100g'] ?? ''),
                    'protein_g' => $this->nullableDecimal($data['protein_g'] ?? ''),
                    'fat_g' => $this->nullableDecimal($data['fat_g'] ?? ''),
                    'saturated_fat_g' => $this->nullableDecimal($data['saturated_fat_g'] ?? ''),
                    'carbs_g' => $this->nullableDecimal($data['carbs_g'] ?? ''),
                    'sugar_g' => $this->nullableDecimal($data['sugar_g'] ?? ''),
                    'fiber_g' => $this->nullableDecimal($data['fiber_g'] ?? ''),
                    'sodium_mg' => $this->nullableDecimal($data['sodium_mg'] ?? ''),
                    'is_active' => ($data['is_active'] ?? '1') === '1',
                ];

                if ($this->enrich) {
                    $densityMatch = $this->matchDensity($name);
                    if ($densityMatch !== null) {
                        $attributes['density_g_per_ml'] = $densityMatch['density_g_per_ml'];
                        $unitId = $this->unitCache[$densityMatch['default_unit']] ?? null;
                        if ($unitId !== null) {
                            $attributes['default_unit_id'] = $unitId;
                        }
                    }
                }

                if ($dryRun) {
                    $existing = Ingredient::where('source', $source)->exists();
                    $label = $existing ? 'UPDATE' : 'CREATE';
                    if ($this->enrich && isset($attributes['density_g_per_ml'])) {
                        $label .= ' +density';
                    }
                    $this->line("{$label} [{$source}] {$name}");
                    $existing ? $updated++ : $created++;

                    continue;
                }

                $existing = Ingredient::where('source', $source)->first();

                if ($existing) {
                    $existing->update($attributes);
                    $ingredient = $existing;
                    $updated++;
                } else {
                    $attributes['source'] = $source;
                    $ingredient = Ingredient::create($attributes);
                    $created++;
                }

                if ($this->enrich) {
                    $wasEnriched = $this->applyEnrichment($ingredient, $name, $categorySlug);
                    if ($wasEnriched) {
                        $enriched++;
                    }
                }
            }
        };

        if ($dryRun) {
            $callback();
        } else {
            DB::transaction($callback);
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'enriched' => $enriched,
        ];
    }

    private function applyEnrichment(Ingredient $ingredient, string $name, string $categorySlug): bool
    {
        $changed = false;

        $allergenIds = $this->matchAllergens($name, $categorySlug);
        if ($allergenIds !== []) {
            $ingredient->allergens()->syncWithoutDetaching($allergenIds);
            $changed = true;
        }

        $aliases = $this->matchAliases($name);
        if ($aliases !== []) {
            foreach ($aliases as $alias) {
                IngredientAlias::firstOrCreate([
                    'ingredient_id' => $ingredient->id,
                    'alias' => $alias,
                ]);
            }
            $changed = true;
        }

        return $changed;
    }

    /**
     * @return array{density_g_per_ml: float, default_unit: string}|null
     */
    private function matchDensity(string $name): ?array
    {
        $nameLower = mb_strtolower($name);

        foreach ($this->densityRules as $rule) {
            foreach ($rule['keywords'] as $keyword) {
                if (str_contains($nameLower, mb_strtolower($keyword))) {
                    return [
                        'density_g_per_ml' => $rule['density_g_per_ml'],
                        'default_unit' => $rule['default_unit'],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @return list<int>
     */
    private function matchAllergens(string $name, string $categorySlug): array
    {
        $nameLower = mb_strtolower($name);
        $matched = [];

        foreach ($this->allergenKeywordRules as $rule) {
            $allergenId = $this->allergenCache[$rule['allergen']] ?? null;
            if ($allergenId === null) {
                continue;
            }

            foreach ($rule['keywords'] as $keyword) {
                if (str_contains($nameLower, mb_strtolower($keyword))) {
                    $matched[$allergenId] = true;
                    break;
                }
            }
        }

        if ($categorySlug !== '') {
            foreach ($this->allergenCategoryRules as $rule) {
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

    /**
     * @return list<string>
     */
    private function matchAliases(string $name): array
    {
        $nameLower = mb_strtolower($name);
        $aliases = [];

        foreach ($this->aliasRules as $rule) {
            foreach ($rule['keywords'] as $keyword) {
                if (str_contains($nameLower, mb_strtolower($keyword))) {
                    foreach ($rule['aliases'] as $alias) {
                        $aliases[] = $alias;
                    }
                    break;
                }
            }
        }

        return $aliases;
    }

    private function loadEnrichmentData(): bool
    {
        $dataDir = database_path('seeders/data');

        $densitiesPath = $dataDir.'/densities.json';
        $allergenRulesPath = $dataDir.'/allergen-rules.json';
        $aliasesPath = $dataDir.'/aliases.json';

        foreach ([$densitiesPath, $allergenRulesPath, $aliasesPath] as $file) {
            if (! file_exists($file)) {
                $this->error("Enrichment file not found: {$file}");

                return false;
            }
        }

        /** @var array{items: list<array{keywords: list<string>, density_g_per_ml: float, default_unit: string}>} $densities */
        $densities = json_decode((string) file_get_contents($densitiesPath), true);
        $this->densityRules = $densities['items'];

        /** @var array{by_keyword: list<array{allergen: string, keywords: list<string>}>, by_category: list<array{allergen: string, category_slugs: list<string>}>} $allergenRules */
        $allergenRules = json_decode((string) file_get_contents($allergenRulesPath), true);
        $this->allergenKeywordRules = $allergenRules['by_keyword'];
        $this->allergenCategoryRules = $allergenRules['by_category'];

        /** @var array{items: list<array{keywords: list<string>, aliases: list<string>}>} $aliases */
        $aliases = json_decode((string) file_get_contents($aliasesPath), true);
        $this->aliasRules = $aliases['items'];

        $this->allergenCache = Allergen::pluck('id', 'slug')->all();
        $this->unitCache = Unit::pluck('id', 'code')->all();

        $this->info('Enrichment data loaded: '.count($this->densityRules).' density rules, '
            .count($this->allergenKeywordRules).' allergen keyword rules, '
            .count($this->allergenCategoryRules).' allergen category rules, '
            .count($this->aliasRules).' alias rules.');

        return true;
    }

    private function loadCategoryCache(): void
    {
        $this->categoryCache = IngredientCategory::pluck('id', 'slug')->all();
    }

    private function uniqueSlug(string $name, string $source): string
    {
        $existing = Ingredient::where('source', $source)->first();
        if ($existing) {
            return $existing->slug;
        }

        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Ingredient::where('slug', $slug)->exists()) {
            $counter++;
            $slug = "{$base}-{$counter}";
        }

        return $slug;
    }

    private function nullableDecimal(string $value): ?float
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : (float) $trimmed;
    }

    private function createLogChannel(): string
    {
        $channel = 'usda-import';

        config([
            "logging.channels.{$channel}" => [
                'driver' => 'single',
                'path' => storage_path('logs/usda-import-'.date('Y-m-d').'.log'),
                'level' => 'warning',
            ],
        ]);

        return $channel;
    }

    private function logError(string $channel, int $row, string $message): void
    {
        $line = "Row {$row}: {$message}";
        Log::channel($channel)->warning($line);
        $this->warn($line);
    }
}
