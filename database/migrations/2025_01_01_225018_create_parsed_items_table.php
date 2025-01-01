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
        Schema::create('parsed_items', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'Class' or 'Function'
            $table->string('name');
            $table->string('file_path');
            $table->unsignedInteger('line_number');
            $table->json('annotations')->nullable();
            $table->json('attributes')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->unique(['type', 'name', 'file_path'], 'parsed_items_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parsed_items');
    }
};
