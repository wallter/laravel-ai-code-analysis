<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMethodAttributesToParsedItemsTable extends Migration
{
    public function up()
    {
        Schema::table('parsed_items', function (Blueprint $table) {
            $table->string('class_name')->nullable();
            $table->string('namespace')->nullable();
            $table->string('visibility')->nullable();
            $table->boolean('is_static')->default(false);
            $table->string('fully_qualified_name')->nullable();
        });
    }

    public function down()
    {
        Schema::table('parsed_items', function (Blueprint $table) {
            $table->dropColumn([
                'class_name',
                'namespace',
                'visibility',
                'is_static',
                'fully_qualified_name',
            ]);
        });
    }
}
