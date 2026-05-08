<?php

use App\Filament\Resources\IngredientResource;
use App\Filament\Resources\IngredientResource\Pages\CreateIngredient;
use App\Filament\Resources\IngredientResource\Pages\EditIngredient;
use App\Filament\Resources\IngredientResource\Pages\ListIngredients;
use App\Models\Allergen;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\Tag;
use App\Models\Unit;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admin can list ingredients', function () {
    $ingredient = Ingredient::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(ListIngredients::class)
        ->assertCanSeeTableRecords([$ingredient]);
});

test('admin can create an ingredient with nutrition data', function () {
    $category = IngredientCategory::create(['slug' => 'dairy', 'name' => 'Dairy']);
    $unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);

    Livewire::actingAs($this->admin)
        ->test(CreateIngredient::class)
        ->fillForm([
            'name' => 'Whole milk',
            'slug' => 'whole-milk',
            'category_id' => $category->id,
            'default_unit_id' => $unit->id,
            'density_g_per_ml' => 1.03,
            'kcal_per_100g' => 61,
            'protein_g' => 3.15,
            'fat_g' => 3.25,
            'carbs_g' => 4.80,
            'fiber_g' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ingredient = Ingredient::where('slug', 'whole-milk')->first();
    expect($ingredient)->not->toBeNull()
        ->and($ingredient->name)->toBe('Whole milk')
        ->and((float) $ingredient->kcal_per_100g)->toBe(61.00)
        ->and((float) $ingredient->protein_g)->toBe(3.15)
        ->and((float) $ingredient->density_g_per_ml)->toBe(1.03)
        ->and($ingredient->category_id)->toBe($category->id)
        ->and($ingredient->created_by)->toBe($this->admin->id);
});

test('admin can edit an ingredient', function () {
    $ingredient = Ingredient::factory()->create([
        'name' => 'Brown rice',
        'slug' => 'brown-rice',
        'kcal_per_100g' => 111,
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditIngredient::class, ['record' => $ingredient->getRouteKey()])
        ->fillForm([
            'name' => 'Brown rice, cooked',
            'slug' => 'brown-rice-cooked',
            'kcal_per_100g' => 123,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($ingredient->fresh()->name)->toBe('Brown rice, cooked')
        ->and((float) $ingredient->fresh()->kcal_per_100g)->toBe(123.00);
});

test('admin can delete an ingredient', function () {
    $ingredient = Ingredient::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(EditIngredient::class, ['record' => $ingredient->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect(Ingredient::find($ingredient->id))->toBeNull();
});

test('ingredient slug must be unique', function () {
    Ingredient::factory()->create(['slug' => 'chicken-breast']);

    Livewire::actingAs($this->admin)
        ->test(CreateIngredient::class)
        ->fillForm([
            'name' => 'Chicken breast',
            'slug' => 'chicken-breast',
        ])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

test('ingredient can have allergens attached', function () {
    $gluten = Allergen::create(['slug' => 'gluten', 'name' => 'Gluten']);
    $lactose = Allergen::create(['slug' => 'lactose', 'name' => 'Lactose']);

    Livewire::actingAs($this->admin)
        ->test(CreateIngredient::class)
        ->fillForm([
            'name' => 'Wheat flour',
            'slug' => 'wheat-flour',
            'allergens' => [$gluten->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ingredient = Ingredient::where('slug', 'wheat-flour')->first();
    expect($ingredient->allergens)->toHaveCount(1)
        ->and($ingredient->allergens->first()->slug)->toBe('gluten');
});

test('ingredient can have tags attached', function () {
    $vegan = Tag::create(['slug' => 'vegan', 'name' => 'Vegan', 'type' => 'diet']);

    Livewire::actingAs($this->admin)
        ->test(CreateIngredient::class)
        ->fillForm([
            'name' => 'Tofu',
            'slug' => 'tofu',
            'tags' => [$vegan->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ingredient = Ingredient::where('slug', 'tofu')->first();
    expect($ingredient->tags)->toHaveCount(1)
        ->and($ingredient->tags->first()->slug)->toBe('vegan');
});

test('ingredient can have aliases', function () {
    $ingredient = Ingredient::factory()->create(['slug' => 'chickpeas', 'name' => 'Chickpeas']);
    $ingredient->aliases()->createMany([
        ['alias' => 'Garbanzo beans'],
        ['alias' => 'Ceci beans'],
    ]);

    expect($ingredient->aliases)->toHaveCount(2)
        ->and($ingredient->aliases->pluck('alias')->toArray())->toContain('Garbanzo beans', 'Ceci beans');
});

test('ingredient model relations work correctly', function () {
    $category = IngredientCategory::create(['slug' => 'grains', 'name' => 'Grains']);
    $unit = Unit::create(['code' => 'g', 'name' => 'gram', 'type' => 'mass', 'to_base_factor' => 1]);

    $ingredient = Ingredient::factory()->create([
        'category_id' => $category->id,
        'default_unit_id' => $unit->id,
        'created_by' => $this->admin->id,
    ]);

    expect($ingredient->category->name)->toBe('Grains')
        ->and($ingredient->defaultUnit->code)->toBe('g')
        ->and($ingredient->creator->id)->toBe($this->admin->id);
});

test('non-admin cannot access ingredient resource', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(IngredientResource::getUrl('index'))
        ->assertForbidden();
});

test('ingredient list can be filtered by category', function () {
    $dairy = IngredientCategory::create(['slug' => 'dairy', 'name' => 'Dairy']);
    $meat = IngredientCategory::create(['slug' => 'meat', 'name' => 'Meat']);

    $milk = Ingredient::factory()->create(['name' => 'Milk', 'slug' => 'milk', 'category_id' => $dairy->id]);
    $chicken = Ingredient::factory()->create(['name' => 'Chicken', 'slug' => 'chicken', 'category_id' => $meat->id]);

    Livewire::actingAs($this->admin)
        ->test(ListIngredients::class)
        ->assertCanSeeTableRecords([$milk, $chicken])
        ->filterTable('category_id', $dairy->id)
        ->assertCanSeeTableRecords([$milk])
        ->assertCanNotSeeTableRecords([$chicken]);
});

test('ingredient list can be filtered by active status', function () {
    $active = Ingredient::factory()->create(['slug' => 'active-item', 'is_active' => true]);
    $inactive = Ingredient::factory()->create(['slug' => 'inactive-item', 'is_active' => false]);

    Livewire::actingAs($this->admin)
        ->test(ListIngredients::class)
        ->assertCanSeeTableRecords([$active, $inactive])
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$inactive]);
});

test('ingredient list can be searched by name', function () {
    $tomato = Ingredient::factory()->create(['name' => 'Tomato', 'slug' => 'tomato']);
    $potato = Ingredient::factory()->create(['name' => 'Potato', 'slug' => 'potato']);

    Livewire::actingAs($this->admin)
        ->test(ListIngredients::class)
        ->searchTable('Tomato')
        ->assertCanSeeTableRecords([$tomato])
        ->assertCanNotSeeTableRecords([$potato]);
});
