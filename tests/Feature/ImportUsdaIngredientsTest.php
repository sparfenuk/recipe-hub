<?php

use App\Models\Ingredient;
use App\Models\IngredientCategory;

beforeEach(function () {
    foreach (['dairy', 'grains-cereals', 'meat', 'nuts-seeds', 'vegetables'] as $slug) {
        IngredientCategory::create(['slug' => $slug, 'name' => ucfirst($slug)]);
    }
});

function fixturePath(): string
{
    return base_path('tests/fixtures/usda-import.csv');
}

it('imports all rows from the fixture CSV', function () {
    $this->artisan('ingredients:import-usda', ['path' => fixturePath()])
        ->assertSuccessful();

    expect(Ingredient::count())->toBe(5);
});

it('sets source field as USDA FDC identifier', function () {
    $this->artisan('ingredients:import-usda', ['path' => fixturePath()])
        ->assertSuccessful();

    expect(Ingredient::where('source', 'USDA FDC #174230')->exists())->toBeTrue()
        ->and(Ingredient::where('source', 'USDA FDC #168917')->exists())->toBeTrue();
});

it('maps category slugs to category IDs', function () {
    $this->artisan('ingredients:import-usda', ['path' => fixturePath()])
        ->assertSuccessful();

    $egg = Ingredient::where('source', 'USDA FDC #174230')->first();
    $dairy = IngredientCategory::where('slug', 'dairy')->first();

    expect($egg->category_id)->toBe($dairy->id);
});

it('stores nutrition values correctly', function () {
    $this->artisan('ingredients:import-usda', ['path' => fixturePath()])
        ->assertSuccessful();

    $egg = Ingredient::where('source', 'USDA FDC #174230')->first();

    expect((float) $egg->kcal_per_100g)->toBe(143.0)
        ->and((float) $egg->protein_g)->toBe(12.56)
        ->and((float) $egg->fat_g)->toBe(9.51)
        ->and((float) $egg->saturated_fat_g)->toBe(3.13)
        ->and((float) $egg->carbs_g)->toBe(0.72)
        ->and((float) $egg->sugar_g)->toBe(0.37)
        ->and((float) $egg->fiber_g)->toBe(0.0)
        ->and((float) $egg->sodium_mg)->toBe(142.0);
});

it('respects the is_active flag from CSV', function () {
    $this->artisan('ingredients:import-usda', ['path' => fixturePath()])
        ->assertSuccessful();

    $active = Ingredient::where('source', 'USDA FDC #174230')->first();
    $inactive = Ingredient::where('source', 'USDA FDC #999006')->first();

    expect($active->is_active)->toBeTrue()
        ->and($inactive->is_active)->toBeFalse();
});

it('generates unique slugs for each ingredient', function () {
    $this->artisan('ingredients:import-usda', ['path' => fixturePath()])
        ->assertSuccessful();

    $slugs = Ingredient::pluck('slug')->all();

    expect($slugs)->toHaveCount(5)
        ->and(array_unique($slugs))->toHaveCount(5);
});

it('is idempotent — re-running updates instead of duplicating', function () {
    $this->artisan('ingredients:import-usda', ['path' => fixturePath()])
        ->assertSuccessful();

    expect(Ingredient::count())->toBe(5);

    $this->artisan('ingredients:import-usda', ['path' => fixturePath()])
        ->assertSuccessful();

    expect(Ingredient::count())->toBe(5);
});

it('updates nutrition data on re-import', function () {
    $this->artisan('ingredients:import-usda', ['path' => fixturePath()])
        ->assertSuccessful();

    $egg = Ingredient::where('source', 'USDA FDC #174230')->first();
    $egg->update(['kcal_per_100g' => 999]);

    $this->artisan('ingredients:import-usda', ['path' => fixturePath()])
        ->assertSuccessful();

    $egg->refresh();
    expect((float) $egg->kcal_per_100g)->toBe(143.0);
});

it('does not write to the database in dry-run mode', function () {
    $this->artisan('ingredients:import-usda', [
        'path' => fixturePath(),
        '--dry-run' => true,
    ])->assertSuccessful();

    expect(Ingredient::count())->toBe(0);
});

it('fails gracefully when CSV file does not exist', function () {
    $this->artisan('ingredients:import-usda', ['path' => '/nonexistent/file.csv'])
        ->assertFailed();
});

it('skips rows with unknown category slugs and logs error', function () {
    $badCsv = tempnam(sys_get_temp_dir(), 'usda');
    file_put_contents($badCsv, implode("\n", [
        'fdc_id,name,category_slug,kcal_per_100g,protein_g,fat_g,saturated_fat_g,carbs_g,sugar_g,fiber_g,sodium_mg,is_active',
        '100001,"Good Ingredient",dairy,100,10,5,2,20,5,3,100,1',
        '100002,"Bad Category Item",nonexistent-category,200,15,8,3,30,8,4,200,1',
    ]));

    $this->artisan('ingredients:import-usda', ['path' => $badCsv])
        ->assertSuccessful();

    expect(Ingredient::count())->toBe(1);

    unlink($badCsv);
});

it('respects the chunk option', function () {
    $this->artisan('ingredients:import-usda', [
        'path' => fixturePath(),
        '--chunk' => 2,
    ])->assertSuccessful();

    expect(Ingredient::count())->toBe(5);
});
