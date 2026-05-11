<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained();
            $table->unsignedSmallInteger('position')->default(0);
            $table->decimal('amount', 10, 3)->default(0);
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('grams_override', 10, 2)->nullable();
            $table->string('note', 255)->nullable();
            $table->boolean('is_optional')->default(false);
            $table->string('group_label', 100)->nullable();

            $table->index(['recipe_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredients');
    }
};
