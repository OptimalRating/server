<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSurveyTypeToSurveyChoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('survey_choices', function (Blueprint $table) {
            // Add the survey_type column (string type or as per your need)
            $table->string('survey_type')->nullable();  // Assuming it's a string. Adjust the type as needed
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('survey_choices', function (Blueprint $table) {
            // Drop the survey_type column if the migration is rolled back
            $table->dropColumn('survey_type');
        });
    }
}
