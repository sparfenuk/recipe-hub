<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'recipe.view',
            'recipe.create',
            'recipe.update',
            'recipe.delete',
            'recipe.publish',
            'ingredient.view',
            'ingredient.create',
            'ingredient.update',
            'ingredient.delete',
            'media.upload',
            'user.manage',
            'user.impersonate',
            'admin.access',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guest = Role::findOrCreate('guest', 'web');
        $guest->syncPermissions([
            'recipe.view',
            'ingredient.view',
        ]);

        $user = Role::findOrCreate('user', 'web');
        $user->syncPermissions([
            'recipe.view',
            'ingredient.view',
        ]);

        $editor = Role::findOrCreate('editor', 'web');
        $editor->syncPermissions([
            'recipe.view',
            'recipe.create',
            'recipe.update',
            'ingredient.view',
            'ingredient.create',
            'ingredient.update',
            'media.upload',
        ]);

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions(Permission::all());
    }
}
