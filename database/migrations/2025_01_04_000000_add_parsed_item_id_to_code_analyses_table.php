<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParsedItemIdToCodeAnalysesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('code_analyses', function (Blueprint $table) {
            $table->foreignId('parsed_item_id')
                  ->after('file_path')
                  ->constrained('parsed_items')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('code_analyses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parsed_item_id');
        });
    }
}
