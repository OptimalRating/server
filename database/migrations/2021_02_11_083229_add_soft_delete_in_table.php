<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSoftDeleteInTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasColumn('countries', 'deleted_at') ) {
            Schema::table('countries', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
        
        if ( !Schema::hasColumn('surveys', 'deleted_at') ) {
            Schema::table('surveys', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
        
        if ( !Schema::hasColumn('survey_choices', 'deleted_at') ) {
            Schema::table('survey_choices', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
        
        if ( !Schema::hasColumn('survey_subjects', 'deleted_at') ) {
            Schema::table('survey_subjects', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
        
        if ( !Schema::hasColumn('survey_votes', 'deleted_at') ) {
            Schema::table('survey_votes', function (Blueprint $table) {
                $table->softDeletes();
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
        if ( Schema::hasColumn('countries', 'deleted_at') ) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }

        if ( !Schema::hasColumn('surveys', 'deleted_at') ) {
            Schema::table('surveys', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }
        
        if ( !Schema::hasColumn('survey_choices', 'deleted_at') ) {
            Schema::table('survey_choices', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }
        
        if ( !Schema::hasColumn('survey_subjects', 'deleted_at') ) {
            Schema::table('survey_subjects', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }
        
        if ( !Schema::hasColumn('survey_votes', 'deleted_at') ) {
            Schema::table('survey_votes', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }
    }
}
