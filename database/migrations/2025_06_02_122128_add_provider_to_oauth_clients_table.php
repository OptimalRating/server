<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    public function up()
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('secret');
        });
    }

    public function down()
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
