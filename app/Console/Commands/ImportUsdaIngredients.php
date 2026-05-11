<?php

namespace App\Console\Commands;

use App\Models\Ingredient;
use App\Models\IngredientCategory;
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

    public function handle(): int
    {
        /** @var string $path */
        $path = $this->argument('path') ?? database_path('seeders/data/usda-curated.csv');

        if (! file_exists($path)) {
            $this->error("CSV file not found: {$path}");

            return self::FAILURE;
        }

        if ($this->option('enrich')) {
            $this->warn('Enrichment data files not yet available (see L2.6). Running without enrichment.');
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
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            $result = $this->processChunk($chunk, $dryRun, $logChannel);
            $created += $result['created'];
            $updated += $result['updated'];
            $skipped += $result['skipped'];
            $errors += $result['errors'];
        }

        fclose($handle);

        $this->newLine();
        $this->info('Import complete.');
        $this->table(
            ['Created', 'Updated', 'Skipped', 'Errors'],
            [[$created, $updated, $skipped, $errors]],
        );

        if ($errors > 0) {
            $this->warn('See log for error details: storage/logs/usda-import-'.date('Y-m-d').'.log');
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<array{row: int, data: array<string, string>}>  $chunk
     * @return array{created: int, updated: int, skipped: int, errors: int}
     */
    private function processChunk(array $chunk, bool $dryRun, string $logChannel): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $callback = function () use ($chunk, $dryRun, $logChannel, &$created, &$updated, &$errors): void {
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

                if ($dryRun) {
                    $existing = Ingredient::where('source', $source)->exists();
                    $this->line(($existing ? 'UPDATE' : 'CREATE')." [{$source}] {$name}");
                    $existing ? $updated++ : $created++;

                    continue;
                }

                $existing = Ingredient::where('source', $source)->first();

                if ($existing) {
                    $existing->update($attributes);
                    $updated++;
                } else {
                    $attributes['source'] = $source;
                    Ingredient::create($attributes);
                    $created++;
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
        ];
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
