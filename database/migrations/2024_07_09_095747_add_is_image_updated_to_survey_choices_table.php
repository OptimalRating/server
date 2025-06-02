<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsImageUpdatedToSurveyChoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Prevent error if the column already exists
        if (!Schema::hasColumn('survey_choices', 'isImageUpdated')) {
            Schema::table('survey_choices', function (Blueprint $table) {
                $table->boolean('isImageUpdated')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('survey_choices', 'isImageUpdated')) {
            Schema::table('survey_choices', function (Blueprint $table) {
                $table->dropColumn('isImageUpdated');
            });
        }
    }
}
