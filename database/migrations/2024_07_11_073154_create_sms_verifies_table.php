<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsVerifiesTable extends Migration
{
    public function up()
{
    if (!Schema::hasTable('sms_verifies')) {
        Schema::create('sms_verifies', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->string('sms');
            $table->timestamp('expired');
            $table->timestamps();
        });
    }
}

    public function down()
    {
        Schema::dropIfExists('sms_verifies');
    }
}
