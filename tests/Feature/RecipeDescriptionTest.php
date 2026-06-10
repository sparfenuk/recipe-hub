<?php

use App\Models\Recipe;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);

    $this->author = User::factory()->create();
});

test('recipe description is sanitized on the detail page', function () {
    Recipe::factory()->published()->create([
        'author_id' => $this->author->id,
        'slug' => 'xss-recipe',
        'description' => '<script>alert(1)</script><p>Safe paragraph</p>'
            .'<a href="javascript:alert(2)">bad link</a>',
    ]);

    $this->get(route('recipes.show', 'xss-recipe'))
        ->assertOk()
        ->assertSee('Safe paragraph')
        ->assertSee('bad link')
        ->assertDontSee('alert(1)', escape: false)
        ->assertDontSee('javascript:alert(2)', escape: false);
});
