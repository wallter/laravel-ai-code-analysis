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
        Schema::create('static_analysis_tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_configuration_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->string('command');
            $table->json('options');
            $table->string('output_format');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('static_analysis_tools');
    }
};
