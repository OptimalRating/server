<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNameToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('name')->nullable(); // or make it not nullable if required
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('name');
    });
}

}
