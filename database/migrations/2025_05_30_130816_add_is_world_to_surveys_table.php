<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('surveys', 'is_world')) {
            Schema::table('surveys', function (Blueprint $table) {
                $table->boolean('is_world')->default(false);
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
        if (Schema::hasColumn('surveys', 'is_world')) {
            Schema::table('surveys', function (Blueprint $table) {
                $table->dropColumn('is_world');
            });
        }
    }
};
