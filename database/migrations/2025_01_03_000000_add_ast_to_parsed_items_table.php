<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAstToParsedItemsTable extends Migration
{
    public function up()
    {
        Schema::table('parsed_items', function (Blueprint $table) {
            $table->longText('ast')->nullable();
        });
    }

    public function down()
    {
        Schema::table('parsed_items', function (Blueprint $table) {
            $table->dropColumn('ast');
        });
    }
}
