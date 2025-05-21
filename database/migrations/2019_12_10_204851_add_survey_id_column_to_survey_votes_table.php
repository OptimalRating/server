<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSurveyIdColumnToSurveyVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('survey_votes', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_id');
            $table->foreign('survey_id')->on('surveys')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('survey_votes', function (Blueprint $table) {
            $table->dropColumn('survey_id');
        });
    }
}
