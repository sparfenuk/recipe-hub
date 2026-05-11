<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 150)->unique();
            $table->string('title', 255);
            $table->string('summary', 500)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('servings')->default(1);
            $table->unsignedSmallInteger('prep_time_min')->default(0);
            $table->unsignedSmallInteger('cook_time_min')->default(0);
            $table->unsignedSmallInteger('total_time_min')->default(0);
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('cuisine_id')->nullable()->constrained('cuisines')->nullOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);

            $table->decimal('total_kcal', 8, 2)->default(0);
            $table->decimal('total_protein_g', 8, 2)->default(0);
            $table->decimal('total_fat_g', 8, 2)->default(0);
            $table->decimal('total_carbs_g', 8, 2)->default(0);
            $table->decimal('total_fiber_g', 8, 2)->default(0);
            $table->decimal('kcal_per_serving', 8, 2)->default(0);
            $table->decimal('protein_per_serving_g', 8, 2)->default(0);
            $table->decimal('fat_per_serving_g', 8, 2)->default(0);
            $table->decimal('carbs_per_serving_g', 8, 2)->default(0);
            $table->decimal('fiber_per_serving_g', 8, 2)->default(0);
            $table->timestamp('nutrition_cached_at')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index('category_id');
            $table->index('cuisine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
