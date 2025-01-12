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
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_configuration_id')->constrained()->onDelete('cascade');
            $table->string('model_name');
            $table->integer('max_tokens')->nullable();
            $table->float('temperature')->nullable();
            $table->boolean('supports_system_message')->default(false);
            $table->string('token_limit_parameter')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
