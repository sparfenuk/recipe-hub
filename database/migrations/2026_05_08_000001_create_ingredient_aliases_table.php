<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->string('alias', 255);

            $table->unique(['ingredient_id', 'alias']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_aliases');
    }
};
