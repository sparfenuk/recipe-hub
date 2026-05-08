#!/usr/bin/env php
<?php

/**
 * USDA FoodData Central → curated ingredient CSV.
 *
 * One-time script. Downloads are at https://fdc.nal.usda.gov/download-datasets
 * Extract the "Full Download" CSV zip into storage/app/usda/ then run:
 *
 *   php scripts/curate-usda.php storage/app/usda
 *
 * Output: database/seeders/data/usda-curated.csv (~600 rows)
 */
if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/curate-usda.php <usda-csv-directory>\n");
    exit(1);
}

$usdaDir = rtrim($argv[1], '/');
$outputPath = __DIR__.'/../database/seeders/data/usda-curated.csv';

$requiredFiles = ['food.csv', 'food_nutrient.csv', 'food_category.csv'];
foreach ($requiredFiles as $f) {
    if (! file_exists("$usdaDir/$f")) {
        fwrite(STDERR, "Missing: $usdaDir/$f\n");
        exit(1);
    }
}

// ── Nutrient IDs we extract (spec §4.8.3) ─────────────────────────────────
$nutrientMap = [
    1008 => 'kcal_per_100g',
    1003 => 'protein_g',
    1004 => 'fat_g',
    1258 => 'saturated_fat_g',
    1005 => 'carbs_g',
    2000 => 'sugar_g',
    1079 => 'fiber_g',
    1093 => 'sodium_mg',
];

// ── USDA category → app ingredient_categories slug ─────────────────────────
$categoryMap = [
    'Dairy and Egg Products' => 'dairy',
    'Spices and Herbs' => 'herbs-spices',
    'Baby Foods' => null, // drop
    'Fats and Oils' => 'oils-fats',
    'Poultry Products' => 'poultry',
    'Soups, Sauces, and Gravies' => 'condiments-sauces',
    'Sausages and Luncheon Meats' => 'meat',
    'Breakfast Cereals' => 'grains-cereals',
    'Fruits and Fruit Juices' => 'fruits',
    'Pork Products' => 'meat',
    'Vegetables and Vegetable Products' => 'vegetables',
    'Nut and Seed Products' => 'nuts-seeds',
    'Beef Products' => 'meat',
    'Beverages' => 'beverages',
    'Finfish and Shellfish Products' => 'seafood',
    'Legumes and Legume Products' => 'legumes',
    'Lamb, Veal, and Game Products' => 'meat',
    'Baked Products' => 'baking',
    'Sweets' => null, // drop aggregates
    'Cereal Grains and Pasta' => 'grains-cereals',
    'Fast Foods' => null, // drop
    'Meals, Entrees, and Side Dishes' => null, // drop
    'Snacks' => 'other',
    'Restaurant Foods' => null, // drop
    'American Indian/Alaska Native Foods' => 'other',
    'Foods from Subway' => null, // drop
    'Foods from Tim Hortons' => null, // drop
    'Foods from McDonald\'s' => null, // drop
    'Alcoholic Beverages' => null, // drop
    'Infant Formula' => null, // drop
    'Supplements' => null, // drop
];

// ── Description keywords that trigger a drop ───────────────────────────────
$dropKeywords = [
    'prepared', 'restaurant', 'entree', 'commercially prepared',
    'frozen meal', 'mre', 'infant formula', 'baby food',
    'supplement', 'meal replacement',
];

// ── Step 1: Load food categories ───────────────────────────────────────────
echo "Loading food categories…\n";
$categories = [];
$fh = fopen("$usdaDir/food_category.csv", 'r');
$header = fgetcsv($fh);
while (($row = fgetcsv($fh)) !== false) {
    $rec = array_combine($header, $row);
    $categories[(int) $rec['id']] = $rec['description'];
}
fclose($fh);
echo '  '.count($categories)." categories loaded.\n";

// ── Step 2: Stream food.csv, keep foundation_food + sr_legacy_food ─────────
echo "Reading food.csv…\n";
$foods = []; // fdc_id => ['description', 'category_id', 'data_type']
$fh = fopen("$usdaDir/food.csv", 'r');
$header = fgetcsv($fh);
$totalRead = 0;
$kept = 0;
while (($row = fgetcsv($fh)) !== false) {
    $totalRead++;
    $rec = array_combine($header, $row);

    if (! in_array($rec['data_type'], ['foundation_food', 'sr_legacy_food'], true)) {
        continue;
    }

    $catId = (int) $rec['food_category_id'];
    $catName = $categories[$catId] ?? 'Unknown';
    $appCat = $categoryMap[$catName] ?? 'other';

    // Drop entire categories mapped to null
    if ($appCat === null || ! isset($categoryMap[$catName]) && $catName !== 'Unknown') {
        // If category is not in our map at all, keep as "other" unless mapped to null
    }
    if (array_key_exists($catName, $categoryMap) && $categoryMap[$catName] === null) {
        continue;
    }

    $desc = $rec['description'];
    $descLower = mb_strtolower($desc);

    // Drop by keyword
    $dropped = false;
    foreach ($dropKeywords as $kw) {
        if (str_contains($descLower, $kw)) {
            $dropped = true;
            break;
        }
    }
    if ($dropped) {
        continue;
    }

    $fdcId = (int) $rec['fdc_id'];
    $foods[$fdcId] = [
        'fdc_id' => $fdcId,
        'description' => $desc,
        'usda_cat' => $catName,
        'app_cat' => $appCat ?? 'other',
        'data_type' => $rec['data_type'],
    ];
    $kept++;
}
fclose($fh);
echo "  $totalRead total rows, $kept kept after category + keyword filter.\n";

if ($kept === 0) {
    fwrite(STDERR, "No foods passed filters. Check input files.\n");
    exit(1);
}

$fdcIds = array_keys($foods);

// ── Step 3: Stream food_nutrient.csv, collect only needed nutrients ─────────
echo "Reading food_nutrient.csv (this may take a minute)…\n";
$nutrients = []; // fdc_id => [column => amount]
$fh = fopen("$usdaDir/food_nutrient.csv", 'r');
$header = fgetcsv($fh);
$nutrientIdSet = array_keys($nutrientMap);
$fdcIdSet = array_flip($fdcIds);
$nutRows = 0;
while (($row = fgetcsv($fh)) !== false) {
    $rec = array_combine($header, $row);
    $fdcId = (int) $rec['fdc_id'];
    $nutrientId = (int) $rec['nutrient_id'];

    if (! isset($fdcIdSet[$fdcId])) {
        continue;
    }
    if (! in_array($nutrientId, $nutrientIdSet, true)) {
        continue;
    }

    $col = $nutrientMap[$nutrientId];
    $nutrients[$fdcId][$col] = round((float) $rec['amount'], 2);
    $nutRows++;
}
fclose($fh);
echo "  $nutRows nutrient values collected.\n";

// ── Step 4: Normalize names ────────────────────────────────────────────────
echo "Normalizing names…\n";

function normalizeName(string $desc): string
{
    // USDA format: "Beef, ground, 80% lean meat / 20% fat, raw"
    // Target: "Ground beef, 80% lean, raw"

    $name = $desc;

    // Convert ALL CAPS to title case
    if (mb_strtoupper($name) === $name) {
        $name = mb_convert_case($name, MB_CASE_TITLE);
    }

    // Trim trailing commas and whitespace
    $name = trim($name, " ,\t\n\r\0\x0B");

    // Capitalize first letter
    $name = mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1);

    return $name;
}

foreach ($foods as &$food) {
    $food['name'] = normalizeName($food['description']);
}
unset($food);

// ── Step 5: Deduplicate near-identical variants ────────────────────────────
echo "Deduplicating…\n";

function groupKey(string $name): string
{
    $cleaned = str_replace(',', ' ', mb_strtolower($name));
    // Strip percentages and standalone numbers to group variants like "80% lean" / "85% lean"
    $cleaned = preg_replace('/\d+(\.\d+)?%?/', '', $cleaned);
    $words = preg_split('/\s+/', $cleaned, -1, PREG_SPLIT_NO_EMPTY);

    return implode(' ', array_slice($words, 0, 3));
}

$groups = [];
foreach ($foods as $fdcId => $food) {
    $key = $food['app_cat'].'|'.groupKey($food['name']);
    $groups[$key][] = $fdcId;
}

$activeCount = 0;
$inactiveCount = 0;
foreach ($groups as $ids) {
    if (count($ids) <= 2) {
        foreach ($ids as $id) {
            $foods[$id]['is_active'] = true;
            $activeCount++;
        }
    } else {
        // Prefer foundation_food over sr_legacy_food, then alphabetical
        usort($ids, function ($a, $b) use ($foods) {
            $typeOrder = ['foundation_food' => 0, 'sr_legacy_food' => 1];
            $ta = $typeOrder[$foods[$a]['data_type']] ?? 2;
            $tb = $typeOrder[$foods[$b]['data_type']] ?? 2;
            if ($ta !== $tb) {
                return $ta - $tb;
            }

            return strcmp($foods[$a]['name'], $foods[$b]['name']);
        });

        foreach ($ids as $i => $id) {
            $foods[$id]['is_active'] = ($i < 2);
            if ($i < 2) {
                $activeCount++;
            } else {
                $inactiveCount++;
            }
        }
    }
}

echo "  Active: $activeCount, Inactive (duplicates kept for traceability): $inactiveCount\n";

// ── Step 6: Build output rows ──────────────────────────────────────────────
echo "Building output…\n";

$outputColumns = [
    'fdc_id', 'name', 'category_slug', 'kcal_per_100g', 'protein_g',
    'fat_g', 'saturated_fat_g', 'carbs_g', 'sugar_g', 'fiber_g',
    'sodium_mg', 'is_active',
];

$rows = [];
$missingNutrition = 0;
foreach ($foods as $fdcId => $food) {
    $nut = $nutrients[$fdcId] ?? [];

    if (! isset($nut['kcal_per_100g'])) {
        $missingNutrition++;

        continue;
    }

    $rows[] = [
        'fdc_id' => $fdcId,
        'name' => $food['name'],
        'category_slug' => $food['app_cat'],
        'kcal_per_100g' => $nut['kcal_per_100g'] ?? '',
        'protein_g' => $nut['protein_g'] ?? '',
        'fat_g' => $nut['fat_g'] ?? '',
        'saturated_fat_g' => $nut['saturated_fat_g'] ?? '',
        'carbs_g' => $nut['carbs_g'] ?? '',
        'sugar_g' => $nut['sugar_g'] ?? '',
        'fiber_g' => $nut['fiber_g'] ?? '',
        'sodium_mg' => $nut['sodium_mg'] ?? '',
        'is_active' => $food['is_active'] ? '1' : '0',
    ];
}

if ($missingNutrition > 0) {
    echo "  Skipped $missingNutrition items with no calorie data.\n";
}

// Sort by category then name
usort($rows, function ($a, $b) {
    $c = strcmp($a['category_slug'], $b['category_slug']);

    return $c !== 0 ? $c : strcmp($a['name'], $b['name']);
});

// ── Step 7: Write CSV ──────────────────────────────────────────────────────
$dir = dirname($outputPath);
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$fh = fopen($outputPath, 'w');
fputcsv($fh, $outputColumns);
foreach ($rows as $row) {
    fputcsv($fh, $row);
}
fclose($fh);

$activeOut = count(array_filter($rows, fn ($r) => $r['is_active'] === '1'));
$totalOut = count($rows);
echo "\nDone! Wrote $totalOut rows ($activeOut active) to $outputPath\n";
