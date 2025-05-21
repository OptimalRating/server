<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKeywordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('keywords', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key');
            $table->string('default');
            $table->string('type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * keywords table
     * id, key, default
     */

    /***
     * translation table
     * country_id, keyword_id, translation
     */

    /**
     * translation_localization
     * id, key, translation, default
     */

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('keywords');
    }
}
