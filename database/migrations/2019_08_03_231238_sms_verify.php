<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SmsVerify extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_verifies', function (Blueprint $table){
            $table->increments('id');
            $table->string('phone_number');
            $table->string('sms_type'); // info, verify, notification
            $table->string('sms'); //sms content
            $table->string('token');
            $table->enum('status', ['p','d','a'])->default('p');
            $table->timestamp('expired');
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
        Schema::dropIfExists('sms_verifies');
    }
}
