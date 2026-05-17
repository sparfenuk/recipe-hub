<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Cuisine;
use App\Models\Recipe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoCuisineRecipes extends Command
{
    /** @var string */
    protected $signature = 'recipes:auto-cuisine
                            {--dry-run : Report cuisine matches without writing to the database}';

    /** @var string */
    protected $description = 'Auto-assign cuisine to published recipes by matching keywords against the English title. Only fills empty cuisine_id; admin edits survive.';

    /** @var list<array{cuisine: string, keywords: list<string>}> */
    private array $rules = [];

    public function handle(): int
    {
        $rulesPath = database_path('seeders/data/cuisine-rules.json');

        if (! file_exists($rulesPath)) {
            $this->error("Rules file not found: {$rulesPath}");

            return self::FAILURE;
        }

        /** @var array{rules: list<array{cuisine: string, keywords: list<string>}>} $parsed */
        $parsed = json_decode((string) file_get_contents($rulesPath), true);
        $this->rules = $parsed['rules'];

        $cuisineCache = Cuisine::pluck('id', 'slug')->all();
        /** @var array<int, string> $cuisineIdToSlug */
        $cuisineIdToSlug = array_flip($cuisineCache);

        foreach ($this->rules as $rule) {
            if (! isset($cuisineCache[$rule['cuisine']])) {
                $this->warn("Skipping unknown cuisine slug: {$rule['cuisine']}");
            }
        }

        // Snapshot per-cuisine assignment counts BEFORE this run.
        /** @var array<string, int> $alreadyAssigned */
        $alreadyAssigned = [];
        $alreadyRows = DB::table('recipes')
            ->where('status', 'published')
            ->whereNotNull('cuisine_id')
            ->select('cuisine_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('cuisine_id')
            ->get();
        foreach ($alreadyRows as $row) {
            $slug = $cuisineIdToSlug[$row->cuisine_id] ?? null;
            if ($slug !== null) {
                $alreadyAssigned[$slug] = (int) $row->cnt;
            }
        }

        $dryRun = (bool) $this->option('dry-run');

        /** @var array<string, int> $stats */
        $stats = [];
        $unmatched = 0;

        Recipe::where('status', 'published')
            ->whereNull('cuisine_id')
            ->chunkById(100, function ($recipes) use ($cuisineCache, $dryRun, &$stats, &$unmatched): void {
                foreach ($recipes as $recipe) {
                    /** @var Recipe $recipe */
                    $title = $recipe->getTranslation('title', 'en', false);
                    if (! is_string($title) || $title === '') {
                        $unmatched++;

                        continue;
                    }

                    $matchedSlug = $this->matchCuisine($title);
                    if ($matchedSlug === null) {
                        $unmatched++;

                        continue;
                    }

                    $cuisineId = $cuisineCache[$matchedSlug] ?? null;
                    if ($cuisineId === null) {
                        $unmatched++;

                        continue;
                    }

                    $stats[$matchedSlug] = ($stats[$matchedSlug] ?? 0) + 1;

                    if (! $dryRun) {
                        $recipe->update(['cuisine_id' => $cuisineId]);
                    }
                }
            });

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '').'Cuisine auto-assignment complete.');

        $rows = [];
        $totalAlready = 0;
        $totalNew = 0;
        foreach ($this->rules as $rule) {
            $slug = $rule['cuisine'];
            $already = $alreadyAssigned[$slug] ?? 0;
            $new = $stats[$slug] ?? 0;
            $totalAlready += $already;
            $totalNew += $new;
            $rows[] = [$slug, $already, $new, $already + $new];
        }
        $this->table(['Cuisine', 'Already assigned', 'New this run', 'Total'], $rows);

        $processed = $totalNew + $unmatched;
        $this->newLine();
        $this->line("Skipped (already had cuisine): {$totalAlready}");
        $this->line("Processed this run: {$processed} (matched {$totalNew}, no keyword hit {$unmatched})");
        if ($dryRun && $totalNew > 0) {
            $this->line('  [DRY RUN] above matches were NOT written.');
        }

        return self::SUCCESS;
    }

    private function matchCuisine(string $title): ?string
    {
        $titleLower = mb_strtolower($title);

        foreach ($this->rules as $rule) {
            foreach ($rule['keywords'] as $keyword) {
                $pattern = '/\b'.preg_quote(mb_strtolower($keyword), '/').'\b/u';
                if (preg_match($pattern, $titleLower) === 1) {
                    return $rule['cuisine'];
                }
            }
        }

        return null;
    }
}
