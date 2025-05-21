<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCascadeToSurveysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('surveys', function (Blueprint $table) {
            // Check if the foreign key exists before dropping
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('surveys');

            $foreignKeyNames = [];
            foreach ($foreignKeys as $foreignKey) {
                $foreignKeyNames[] = $foreignKey->getName();
            }

            if (in_array('surveys_category_id_foreign', $foreignKeyNames)) {
                $table->dropForeign(['category_id']);
            }

            if (in_array('surveys_user_id_foreign', $foreignKeyNames)) {
                $table->dropForeign(['user_id']);
            }

            // Re-add the foreign keys with cascade delete
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('surveys', function (Blueprint $table) {
            // Drop the cascade foreign keys
            $table->dropForeign(['category_id']);
            $table->dropForeign(['user_id']);
            
            // Re-add the foreign keys without cascade delete
            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
}
