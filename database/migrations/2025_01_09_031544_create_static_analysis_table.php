<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('static_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('code_analysis_id')->constrained()->onDelete('cascade');
            $table->string('tool'); // e.g., PHPStan
            $table->json('results');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('static_analyses');
    }
};
