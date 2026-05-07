<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->enum('sex', ['male', 'female'])->nullable();
            $table->date('birth_date')->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->enum('activity_level', ['sedentary', 'lightly_active', 'moderately_active', 'very_active', 'extremely_active'])->nullable();
            $table->unsignedSmallInteger('daily_kcal_target')->nullable();
            $table->unsignedTinyInteger('p_pct')->default(30);
            $table->unsignedTinyInteger('f_pct')->default(30);
            $table->unsignedTinyInteger('c_pct')->default(40);
            $table->enum('units_pref', ['metric', 'imperial'])->default('metric');
            $table->string('timezone', 64)->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
