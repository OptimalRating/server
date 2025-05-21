<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePendingFriendsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pending_friends', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user');
            $table->integer('friend');
            $table->enum(
                'status',
                ['active','passive', 'pending','obstacle']
            )->default('pending');
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
        Schema::dropIfExists('pending_friends');
    }
}
