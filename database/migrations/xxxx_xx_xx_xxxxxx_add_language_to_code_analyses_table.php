<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLanguageToCodeAnalysesTable extends Migration
{
    public function up()
    {
        Schema::table('code_analyses', function (Blueprint $table) {
            $table->string('language')->default('unknown')->after('completed_passes');
        });
    }

    public function down()
    {
        Schema::table('code_analyses', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
}
