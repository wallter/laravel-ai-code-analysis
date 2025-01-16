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
        Schema::create('ai_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('code_analysis_id')->constrained()->onDelete('cascade');
            $table->string('pass_name');
            $table->longText('prompt_text');
            $table->longText('response_text')->nullable();
            $table->json('metadata')->nullable();
            $table->decimal('cost_estimate_usd', 10, 6)->nullable();
            $table->string('content_type')->nullable();
            $table->timestamps();

            // Add unique constraint to prevent duplicate pass entries per analysis
            $table->unique(['code_analysis_id', 'pass_name'], 'ai_results_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_results');
    }
};
