<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAiOutputToCodeAnalysesTable extends Migration
{
    public function up()
    {
        Schema::table('code_analyses', function (Blueprint $table) {
            // Add a nullable TEXT column to store AI-generated outputs
            $table->text('ai_output')->nullable()->after('analysis');
        });
    }

    public function down()
    {
        Schema::table('code_analyses', function (Blueprint $table) {
            // Drop the ai_output column if rolling back
            $table->dropColumn('ai_output');
        });
    }
}
