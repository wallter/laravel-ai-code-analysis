<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParsedItemsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parsed_items', function (Blueprint $table) {
            $table->id();

            // Core identifiers
            $table->string('type'); // 'Class', 'Function', 'Method', etc.
            $table->string('name');
            $table->string('file_path');
            $table->unsignedInteger('line_number')->default(0);

            // Additional attributes
            $table->string('class_name')->nullable();
            $table->string('namespace')->nullable();
            $table->string('visibility')->nullable();
            $table->boolean('is_static')->default(false);
            $table->string('fully_qualified_name')->nullable();

            // AI and operation-related fields
            $table->text('operation_summary')->nullable();
            $table->json('called_methods')->nullable();

            // AST and other details
            $table->longText('ast')->nullable();

            // JSON fields for extra details
            $table->json('annotations')->nullable();
            $table->json('attributes')->nullable();
            $table->json('details')->nullable();

            $table->timestamps();

            // Ensure uniqueness to prevent duplicate entries
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
}
