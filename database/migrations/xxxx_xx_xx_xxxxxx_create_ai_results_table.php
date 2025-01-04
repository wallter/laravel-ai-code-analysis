<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiResultsTable extends Migration
{
    public function up()
    {
        Schema::create('ai_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parsed_item_id')->constrained()->onDelete('cascade');
            $table->json('analysis')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_results');
    }
}
