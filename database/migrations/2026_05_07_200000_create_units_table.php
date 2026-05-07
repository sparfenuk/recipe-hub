<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 50);
            $table->enum('type', ['mass', 'volume', 'count']);
            $table->decimal('to_base_factor', 12, 6);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
