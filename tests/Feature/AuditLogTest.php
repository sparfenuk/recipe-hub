<?php

use App\Filament\Resources\AuditResource;
use App\Filament\Resources\AuditResource\Pages\ListAudits;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\Cuisine;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\Recipe;
use App\Models\Tag;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    Audit::query()->delete();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    Audit::query()->delete();
});

test('recipe creation generates audit record', function () {
    $category = Category::create(['slug' => 'main', 'name' => 'Main']);
    $cuisine = Cuisine::create(['slug' => 'italian', 'name' => 'Italian']);

    $recipe = Recipe::create([
        'slug' => 'test-recipe',
        'title' => 'Test Recipe',
        'summary' => 'A test',
        'servings' => 4,
        'difficulty' => 'easy',
        'status' => 'draft',
        'category_id' => $category->id,
        'cuisine_id' => $cuisine->id,
        'author_id' => $this->admin->id,
    ]);

    $audit = Audit::where('auditable_type', Recipe::class)
        ->where('auditable_id', $recipe->id)
        ->where('event', 'created')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->new_values)->toHaveKey('title')
        ->and(json_decode((string) $audit->new_values['title'], true))->toMatchArray(['en' => 'Test Recipe']);
});

test('recipe update generates audit with old and new values', function () {
    $recipe = Recipe::factory()->create(['title' => 'Old Title']);

    $recipe->update(['title' => 'New Title']);

    $audit = Audit::where('auditable_type', Recipe::class)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->old_values)->toHaveKey('title')
        ->and($audit->new_values)->toHaveKey('title')
        ->and(json_decode((string) $audit->old_values['title'], true))->toMatchArray(['en' => 'Old Title'])
        ->and(json_decode((string) $audit->new_values['title'], true))->toMatchArray(['en' => 'New Title']);
});

test('recipe soft delete generates audit', function () {
    $recipe = Recipe::factory()->create();

    $recipe->delete();

    $audit = Audit::where('auditable_type', Recipe::class)
        ->where('event', 'deleted')
        ->first();

    expect($audit)->not->toBeNull();
});

test('ingredient creation generates audit', function () {
    $unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);

    $ingredient = Ingredient::create([
        'slug' => 'test-flour',
        'name' => 'Test Flour',
        'default_unit_id' => $unit->id,
        'is_active' => true,
    ]);

    $audit = Audit::where('auditable_type', Ingredient::class)
        ->where('auditable_id', $ingredient->id)
        ->where('event', 'created')
        ->first();

    expect($audit)->not->toBeNull();
});

test('user creation generates audit', function () {
    Audit::query()->delete();
    $user = User::factory()->create(['name' => 'Audited User']);

    $audit = Audit::where('auditable_type', User::class)
        ->where('auditable_id', $user->id)
        ->where('event', 'created')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->new_values)->toHaveKey('name', 'Audited User');
});

test('user audit excludes password and remember_token', function () {
    Audit::query()->delete();
    $user = User::factory()->create();

    $audit = Audit::where('auditable_type', User::class)
        ->where('auditable_id', $user->id)
        ->first();

    expect($audit->new_values)->not->toHaveKey('password')
        ->and($audit->new_values)->not->toHaveKey('remember_token');
});

test('taxonomy models generate audits', function () {
    Audit::query()->delete();

    Category::create(['slug' => 'soup', 'name' => 'Soup']);
    Cuisine::create(['slug' => 'thai', 'name' => 'Thai']);
    Tag::create(['slug' => 'vegan', 'name' => 'Vegan', 'type' => 'diet']);
    Allergen::create(['slug' => 'gluten', 'name' => 'Gluten']);
    IngredientCategory::create(['slug' => 'grains', 'name' => 'Grains']);

    expect(Audit::where('auditable_type', Category::class)->count())->toBe(1)
        ->and(Audit::where('auditable_type', Cuisine::class)->count())->toBe(1)
        ->and(Audit::where('auditable_type', Tag::class)->count())->toBe(1)
        ->and(Audit::where('auditable_type', Allergen::class)->count())->toBe(1)
        ->and(Audit::where('auditable_type', IngredientCategory::class)->count())->toBe(1);
});

test('taxonomy update generates audit', function () {
    $category = Category::create(['slug' => 'soup', 'name' => ['en' => 'Soup', 'uk' => 'Суп']]);
    $category->update(['name' => ['en' => 'Soups & Stews', 'uk' => 'Супи та рагу']]);

    $audit = Audit::where('auditable_type', Category::class)
        ->where('event', 'updated')
        ->first();

    expect($audit)->not->toBeNull()
        ->and(json_decode((string) $audit->old_values['name'], true))->toMatchArray(['en' => 'Soup', 'uk' => 'Суп'])
        ->and(json_decode((string) $audit->new_values['name'], true))->toMatchArray(['en' => 'Soups & Stews', 'uk' => 'Супи та рагу']);
});

test('admin can access audit log page', function () {
    Livewire::actingAs($this->admin)
        ->test(ListAudits::class)
        ->assertSuccessful();
});

test('audit log lists records', function () {
    Audit::query()->delete();
    Category::create(['slug' => 'soup', 'name' => 'Soup']);
    Cuisine::create(['slug' => 'thai', 'name' => 'Thai']);

    Livewire::actingAs($this->admin)
        ->test(ListAudits::class)
        ->assertCanSeeTableRecords(Audit::all());
});

test('audit log filters by event', function () {
    Audit::query()->delete();
    $category = Category::create(['slug' => 'soup', 'name' => 'Soup']);
    $category->update(['name' => 'Soups']);

    $created = Audit::where('event', 'created')->first();
    $updated = Audit::where('event', 'updated')->first();

    Livewire::actingAs($this->admin)
        ->test(ListAudits::class)
        ->filterTable('event', 'updated')
        ->assertCanSeeTableRecords([$updated])
        ->assertCanNotSeeTableRecords([$created]);
});

test('audit log filters by model type', function () {
    Audit::query()->delete();
    Category::create(['slug' => 'soup', 'name' => 'Soup']);
    Cuisine::create(['slug' => 'thai', 'name' => 'Thai']);

    $categoryAudit = Audit::where('auditable_type', Category::class)->first();
    $cuisineAudit = Audit::where('auditable_type', Cuisine::class)->first();

    Livewire::actingAs($this->admin)
        ->test(ListAudits::class)
        ->filterTable('auditable_type', Category::class)
        ->assertCanSeeTableRecords([$categoryAudit])
        ->assertCanNotSeeTableRecords([$cuisineAudit]);
});

test('non-admin cannot access audit log', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(AuditResource::getUrl('index'))
        ->assertForbidden();
});

test('prune command deletes old audits', function () {
    Audit::query()->delete();
    Category::create(['slug' => 'old', 'name' => 'Old']);
    Category::create(['slug' => 'new', 'name' => 'New']);

    expect(Audit::count())->toBe(2);

    Audit::where('auditable_type', Category::class)
        ->orderBy('id')
        ->first()
        ->update(['created_at' => now()->subDays(91)]);

    $this->artisan('audits:prune --days=90')
        ->expectsOutputToContain('Pruned 1 audit')
        ->assertExitCode(0);

    expect(Audit::where('auditable_type', Category::class)->count())->toBe(1);
});

test('prune command respects custom retention days', function () {
    Audit::query()->delete();
    Category::create(['slug' => 'test', 'name' => 'Test']);

    Audit::query()->update(['created_at' => now()->subDays(31)]);

    $this->artisan('audits:prune --days=30')
        ->expectsOutputToContain('Pruned 1 audit')
        ->assertExitCode(0);

    expect(Audit::count())->toBe(0);
});

test('prune command keeps recent audits', function () {
    Audit::query()->delete();
    Category::create(['slug' => 'test', 'name' => 'Test']);

    expect(Audit::count())->toBe(1);

    $this->artisan('audits:prune --days=90')
        ->expectsOutputToContain('Pruned 0 audit')
        ->assertExitCode(0);

    expect(Audit::count())->toBe(1);
});

test('prune is scheduled daily', function () {
    $schedule = app(Schedule::class);

    $events = collect($schedule->events())->filter(
        fn ($event) => str_contains($event->command ?? '', 'audits:prune')
    );

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('0 0 * * *');
});
