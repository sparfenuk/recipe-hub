<?php

use App\Filament\Resources\AllergenResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\CuisineResource;
use App\Filament\Resources\IngredientCategoryResource;
use App\Filament\Resources\TagResource;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\Cuisine;
use App\Models\IngredientCategory;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admin can list ingredient categories', function () {
    IngredientCategory::create(['slug' => 'vegetables', 'name' => 'Vegetables']);

    Livewire::actingAs($this->admin)
        ->test(IngredientCategoryResource\Pages\ManageIngredientCategories::class)
        ->assertCanSeeTableRecords(IngredientCategory::all());
});

test('admin can create an ingredient category', function () {
    Livewire::actingAs($this->admin)
        ->test(IngredientCategoryResource\Pages\ManageIngredientCategories::class)
        ->callAction(CreateAction::class, [
            'name' => 'Vegetables',
            'slug' => 'vegetables',
        ]);

    expect(IngredientCategory::where('slug', 'vegetables')->exists())->toBeTrue();
});

test('admin can edit an ingredient category', function () {
    $category = IngredientCategory::create(['slug' => 'vegetables', 'name' => 'Vegetables']);

    Livewire::actingAs($this->admin)
        ->test(IngredientCategoryResource\Pages\ManageIngredientCategories::class)
        ->callTableAction('edit', $category, [
            'name' => 'Fresh Vegetables',
            'slug' => 'fresh-vegetables',
        ]);

    expect($category->fresh()->name)->toBe('Fresh Vegetables');
});

test('admin can delete an ingredient category', function () {
    $category = IngredientCategory::create(['slug' => 'vegetables', 'name' => 'Vegetables']);

    Livewire::actingAs($this->admin)
        ->test(IngredientCategoryResource\Pages\ManageIngredientCategories::class)
        ->callTableAction(DeleteAction::class, $category);

    expect(IngredientCategory::count())->toBe(0);
});

test('admin can list cuisines', function () {
    Cuisine::create(['slug' => 'italian', 'name' => 'Italian']);

    Livewire::actingAs($this->admin)
        ->test(CuisineResource\Pages\ManageCuisines::class)
        ->assertCanSeeTableRecords(Cuisine::all());
});

test('admin can create a cuisine', function () {
    Livewire::actingAs($this->admin)
        ->test(CuisineResource\Pages\ManageCuisines::class)
        ->callAction(CreateAction::class, [
            'name' => 'Thai',
            'slug' => 'thai',
        ]);

    expect(Cuisine::where('slug', 'thai')->exists())->toBeTrue();
});

test('admin can edit a cuisine', function () {
    $cuisine = Cuisine::create(['slug' => 'italian', 'name' => 'Italian']);

    Livewire::actingAs($this->admin)
        ->test(CuisineResource\Pages\ManageCuisines::class)
        ->callTableAction('edit', $cuisine, [
            'name' => 'Southern Italian',
            'slug' => 'southern-italian',
        ]);

    expect($cuisine->fresh()->name)->toBe('Southern Italian');
});

test('admin can delete a cuisine', function () {
    $cuisine = Cuisine::create(['slug' => 'italian', 'name' => 'Italian']);

    Livewire::actingAs($this->admin)
        ->test(CuisineResource\Pages\ManageCuisines::class)
        ->callTableAction(DeleteAction::class, $cuisine);

    expect(Cuisine::count())->toBe(0);
});

test('admin can list tags', function () {
    Tag::create(['slug' => 'vegan', 'name' => 'Vegan', 'type' => 'diet']);

    Livewire::actingAs($this->admin)
        ->test(TagResource\Pages\ManageTags::class)
        ->assertCanSeeTableRecords(Tag::all());
});

test('admin can create a tag', function () {
    Livewire::actingAs($this->admin)
        ->test(TagResource\Pages\ManageTags::class)
        ->callAction(CreateAction::class, [
            'name' => 'Keto',
            'slug' => 'keto',
            'type' => 'diet',
        ]);

    expect(Tag::where('slug', 'keto')->exists())->toBeTrue()
        ->and(Tag::where('slug', 'keto')->first()->type)->toBe('diet');
});

test('admin can edit a tag', function () {
    $tag = Tag::create(['slug' => 'quick', 'name' => 'Quick', 'type' => 'misc']);

    Livewire::actingAs($this->admin)
        ->test(TagResource\Pages\ManageTags::class)
        ->callTableAction('edit', $tag, [
            'name' => 'Quick & Easy',
            'slug' => 'quick-easy',
            'type' => 'misc',
        ]);

    expect($tag->fresh()->name)->toBe('Quick & Easy');
});

test('admin can delete a tag', function () {
    $tag = Tag::create(['slug' => 'quick', 'name' => 'Quick', 'type' => 'misc']);

    Livewire::actingAs($this->admin)
        ->test(TagResource\Pages\ManageTags::class)
        ->callTableAction(DeleteAction::class, $tag);

    expect(Tag::count())->toBe(0);
});

test('admin can list allergens', function () {
    Allergen::create(['slug' => 'gluten', 'name' => 'Gluten']);

    Livewire::actingAs($this->admin)
        ->test(AllergenResource\Pages\ManageAllergens::class)
        ->assertCanSeeTableRecords(Allergen::all());
});

test('admin can create an allergen', function () {
    Livewire::actingAs($this->admin)
        ->test(AllergenResource\Pages\ManageAllergens::class)
        ->callAction(CreateAction::class, [
            'name' => 'Celery',
            'slug' => 'celery',
        ]);

    expect(Allergen::where('slug', 'celery')->exists())->toBeTrue();
});

test('admin can edit an allergen', function () {
    $allergen = Allergen::create(['slug' => 'nuts', 'name' => 'Nuts']);

    Livewire::actingAs($this->admin)
        ->test(AllergenResource\Pages\ManageAllergens::class)
        ->callTableAction('edit', $allergen, [
            'name' => 'Tree Nuts',
            'slug' => 'tree-nuts',
        ]);

    expect($allergen->fresh()->name)->toBe('Tree Nuts');
});

test('admin can delete an allergen', function () {
    $allergen = Allergen::create(['slug' => 'gluten', 'name' => 'Gluten']);

    Livewire::actingAs($this->admin)
        ->test(AllergenResource\Pages\ManageAllergens::class)
        ->callTableAction(DeleteAction::class, $allergen);

    expect(Allergen::count())->toBe(0);
});

test('admin can list categories', function () {
    Category::create(['slug' => 'breakfast', 'name' => 'Breakfast']);

    Livewire::actingAs($this->admin)
        ->test(CategoryResource\Pages\ManageCategories::class)
        ->assertCanSeeTableRecords(Category::all());
});

test('admin can create a category', function () {
    Livewire::actingAs($this->admin)
        ->test(CategoryResource\Pages\ManageCategories::class)
        ->callAction(CreateAction::class, [
            'name' => 'Desserts',
            'slug' => 'desserts',
        ]);

    expect(Category::where('slug', 'desserts')->exists())->toBeTrue();
});

test('admin can edit a category', function () {
    $category = Category::create(['slug' => 'breakfast', 'name' => 'Breakfast']);

    Livewire::actingAs($this->admin)
        ->test(CategoryResource\Pages\ManageCategories::class)
        ->callTableAction('edit', $category, [
            'name' => 'Morning Breakfast',
            'slug' => 'morning-breakfast',
        ]);

    expect($category->fresh()->name)->toBe('Morning Breakfast');
});

test('admin can delete a category', function () {
    $category = Category::create(['slug' => 'breakfast', 'name' => 'Breakfast']);

    Livewire::actingAs($this->admin)
        ->test(CategoryResource\Pages\ManageCategories::class)
        ->callTableAction(DeleteAction::class, $category);

    expect(Category::count())->toBe(0);
});

test('non-admin cannot access taxonomy resources', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(IngredientCategoryResource::getUrl('index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(CuisineResource::getUrl('index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(TagResource::getUrl('index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(AllergenResource::getUrl('index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(CategoryResource::getUrl('index'))
        ->assertForbidden();
});
