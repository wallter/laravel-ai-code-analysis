<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParsedItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parsed_items', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('name');
            $table->string('file_path');
            $table->integer('line_number')->nullable();
            $table->json('annotations')->nullable();
            $table->json('attributes')->nullable();
            $table->json('details')->nullable();
            $table->string('class_name')->nullable();
            $table->string('namespace')->nullable();
            $table->string('visibility')->nullable();
            $table->boolean('is_static')->default(false);
            $table->string('fully_qualified_name')->nullable();
            $table->text('operation_summary')->nullable();
            $table->json('called_methods')->nullable();
            $table->json('ast')->nullable();
            $table->timestamps();

            $table->unique(['type', 'name', 'file_path']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parsed_items');
    }
}
