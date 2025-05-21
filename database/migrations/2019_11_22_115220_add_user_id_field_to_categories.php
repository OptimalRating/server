<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddUserIdFieldToCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {

            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            $table->unsignedBigInteger('user_id')->nullable();
            // my table definitions go here
            $table->foreign('user_id')->references('id')->on('users');
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            //
        });
    }
}
