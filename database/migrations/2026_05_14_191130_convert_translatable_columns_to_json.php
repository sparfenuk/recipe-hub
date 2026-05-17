<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // recipes: title / summary / description -> JSON via add+backfill+drop+rename
        Schema::table('recipes', function (Blueprint $table): void {
            $table->json('title_i18n')->nullable()->after('slug');
            $table->json('summary_i18n')->nullable()->after('title_i18n');
            $table->json('description_i18n')->nullable()->after('summary_i18n');
        });

        DB::statement("UPDATE recipes SET
            title_i18n = JSON_OBJECT('en', title),
            summary_i18n = IF(summary IS NULL, NULL, JSON_OBJECT('en', summary)),
            description_i18n = IF(description IS NULL, NULL, JSON_OBJECT('en', description))
        ");

        Schema::table('recipes', function (Blueprint $table): void {
            $table->dropColumn(['title', 'summary', 'description']);
        });

        Schema::table('recipes', function (Blueprint $table): void {
            $table->renameColumn('title_i18n', 'title');
            $table->renameColumn('summary_i18n', 'summary');
            $table->renameColumn('description_i18n', 'description');
        });

        DB::statement('ALTER TABLE recipes MODIFY title JSON NOT NULL');

        // recipe_steps: body -> JSON
        Schema::table('recipe_steps', function (Blueprint $table): void {
            $table->json('body_i18n')->nullable()->after('position');
        });

        DB::statement("UPDATE recipe_steps SET body_i18n = JSON_OBJECT('en', body)");

        Schema::table('recipe_steps', function (Blueprint $table): void {
            $table->dropColumn('body');
        });

        Schema::table('recipe_steps', function (Blueprint $table): void {
            $table->renameColumn('body_i18n', 'body');
        });

        DB::statement('ALTER TABLE recipe_steps MODIFY body JSON NOT NULL');

        // units: name -> JSON
        Schema::table('units', function (Blueprint $table): void {
            $table->json('name_i18n')->nullable()->after('code');
        });

        DB::statement("UPDATE units SET name_i18n = JSON_OBJECT('en', name)");

        Schema::table('units', function (Blueprint $table): void {
            $table->dropColumn('name');
        });

        Schema::table('units', function (Blueprint $table): void {
            $table->renameColumn('name_i18n', 'name');
        });

        DB::statement('ALTER TABLE units MODIFY name JSON NOT NULL');
    }

    public function down(): void
    {
        // recipes: JSON -> string, taking `en` value
        Schema::table('recipes', function (Blueprint $table): void {
            $table->string('title_str', 255)->nullable()->after('slug');
            $table->string('summary_str', 500)->nullable()->after('title_str');
            $table->text('description_str')->nullable()->after('summary_str');
        });

        DB::statement("UPDATE recipes SET
            title_str = JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')),
            summary_str = JSON_UNQUOTE(JSON_EXTRACT(summary, '$.en')),
            description_str = JSON_UNQUOTE(JSON_EXTRACT(description, '$.en'))
        ");

        Schema::table('recipes', function (Blueprint $table): void {
            $table->dropColumn(['title', 'summary', 'description']);
        });

        Schema::table('recipes', function (Blueprint $table): void {
            $table->renameColumn('title_str', 'title');
            $table->renameColumn('summary_str', 'summary');
            $table->renameColumn('description_str', 'description');
        });

        DB::statement('ALTER TABLE recipes MODIFY title VARCHAR(255) NOT NULL');

        Schema::table('recipe_steps', function (Blueprint $table): void {
            $table->text('body_str')->nullable()->after('position');
        });

        DB::statement("UPDATE recipe_steps SET body_str = JSON_UNQUOTE(JSON_EXTRACT(body, '$.en'))");

        Schema::table('recipe_steps', function (Blueprint $table): void {
            $table->dropColumn('body');
        });

        Schema::table('recipe_steps', function (Blueprint $table): void {
            $table->renameColumn('body_str', 'body');
        });

        DB::statement('ALTER TABLE recipe_steps MODIFY body TEXT NOT NULL');

        Schema::table('units', function (Blueprint $table): void {
            $table->string('name_str', 50)->nullable()->after('code');
        });

        DB::statement("UPDATE units SET name_str = JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))");

        Schema::table('units', function (Blueprint $table): void {
            $table->dropColumn('name');
        });

        Schema::table('units', function (Blueprint $table): void {
            $table->renameColumn('name_str', 'name');
        });

        DB::statement('ALTER TABLE units MODIFY name VARCHAR(50) NOT NULL');
    }
};
