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
        Schema::table('parsed_items', function (Blueprint $table) {
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parsed_items', function (Blueprint $table) {
            //
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOperationSummaryAndCalledMethodsToParsedItemsTable extends Migration
{
    public function up()
    {
        Schema::table('parsed_items', function (Blueprint $table) {
            $table->text('operation_summary')->nullable();
            $table->json('called_methods')->nullable();
        });
    }

    public function down()
    {
        Schema::table('parsed_items', function (Blueprint $table) {
            $table->dropColumn(['operation_summary', 'called_methods']);
        });
    }
}
