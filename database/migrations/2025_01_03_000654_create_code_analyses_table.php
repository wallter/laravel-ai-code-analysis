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
        Schema::create('code_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('file_path')->unique();
            $table->json('ast');
            $table->json('analysis');
            $table->integer('current_pass')->default(0);
            $table->json('completed_passes')->nullable();
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
        Schema::dropIfExists('code_analyses');
    }
};
