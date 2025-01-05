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
        Schema::table('code_analyses', function (Blueprint $table) {
            $table->dropColumn('scores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('code_analyses', function (Blueprint $table) {
            $table->json('scores')->nullable()->after('ai_output');
        });
    }
};
