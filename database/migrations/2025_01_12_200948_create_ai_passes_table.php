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
        Schema::create('ai_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_configuration_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('operation_identifier');
            $table->foreignId('model_id')->nullable()->constrained('ai_models')->onDelete('set null');
            $table->integer('max_tokens')->nullable();
            $table->float('temperature')->nullable();
            $table->string('type');
            $table->text('system_message')->nullable();
            $table->json('prompt_sections')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_passes');
    }
};
