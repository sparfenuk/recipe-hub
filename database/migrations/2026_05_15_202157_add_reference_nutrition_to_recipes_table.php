<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference nutrition is the per-serving figure taken verbatim from the source
 * cookbook PDF. When set, the recipe page shows these values instead of the
 * ingredient-computed cache (which uses USDA generic values and won't exactly
 * match a Ukrainian cookbook's tables). The computed cache remains intact for
 * portion-scaling math; see PortionCalculator for how the two interact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->decimal('ref_kcal_per_serving', 10, 2)->nullable()->after('fiber_per_serving_g');
            $table->decimal('ref_protein_per_serving_g', 10, 2)->nullable()->after('ref_kcal_per_serving');
            $table->decimal('ref_fat_per_serving_g', 10, 2)->nullable()->after('ref_protein_per_serving_g');
            $table->decimal('ref_carbs_per_serving_g', 10, 2)->nullable()->after('ref_fat_per_serving_g');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn([
                'ref_kcal_per_serving',
                'ref_protein_per_serving_g',
                'ref_fat_per_serving_g',
                'ref_carbs_per_serving_g',
            ]);
        });
    }
};
