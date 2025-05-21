<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeEmailNullableInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if the column exists to avoid errors
            if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert the column to NOT NULL if necessary
            if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable(false)->change();
            }
        });
    }
}
