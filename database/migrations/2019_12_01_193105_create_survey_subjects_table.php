<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSurveySubjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('survey_subjects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('survey_id')->unsigned();
            $table->integer('subject_id')->unsigned();
            $table->foreign('survey_id')->references('id')->on('surveys')
                ->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('survey_subjects');
    }
}
