<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables whose `name` column is being converted from VARCHAR to JSON.
     * Format: [table => varchar length for `down()` rollback].
     *
     * @var array<string, int>
     */
    private array $tables = [
        'ingredients' => 255,
        'ingredient_categories' => 100,
        'categories' => 100,
        'cuisines' => 100,
        'tags' => 100,
        'allergens' => 100,
    ];

    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table): void {
            $table->dropIndex(['name']);
        });

        foreach (array_keys($this->tables) as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->json('name_i18n')->nullable()->after('slug');
            });

            DB::statement("UPDATE {$table} SET name_i18n = JSON_OBJECT('en', name)");

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('name');
            });

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->renameColumn('name_i18n', 'name');
            });

            DB::statement("ALTER TABLE {$table} MODIFY name JSON NOT NULL");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table => $length) {
            Schema::table($table, function (Blueprint $blueprint) use ($length): void {
                $blueprint->string('name_str', $length)->nullable()->after('slug');
            });

            DB::statement("UPDATE {$table} SET name_str = JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))");

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('name');
            });

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->renameColumn('name_str', 'name');
            });

            DB::statement("ALTER TABLE {$table} MODIFY name VARCHAR({$length}) NOT NULL");
        }

        Schema::table('ingredients', function (Blueprint $table): void {
            $table->index('name');
        });
    }
};
