<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCategoryIdToKeywordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('keywords', function (Blueprint $table) {
        $table->unsignedBigInteger('category_id')->nullable()->after('id'); // Adjust the column position as needed
        
        // Add foreign key constraint if 'categories' table exists
        $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
    });
}

public function down()
{
    Schema::table('keywords', function (Blueprint $table) {
        $table->dropForeign(['category_id']);
        $table->dropColumn('category_id');
    });
}

}
