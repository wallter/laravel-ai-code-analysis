<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPassTrackingToCodeAnalysesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('code_analyses', function (Blueprint $table) {
            $table->integer('current_pass')->default(0)->after('ai_output');
            $table->json('completed_passes')->nullable()->after('current_pass');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('code_analyses', function (Blueprint $table) {
            $table->dropColumn(['current_pass', 'completed_passes']);
        });
    }
}
