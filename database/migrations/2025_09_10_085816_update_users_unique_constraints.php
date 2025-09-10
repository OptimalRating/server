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
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop existing unique index on email if it exists
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('users');
            if (array_key_exists('users_email_unique', $indexes)) {
                $table->dropUnique('users_email_unique');
            }

            // Add composite unique constraint: email + provider
            $table->unique(['email', 'provider']);
        });
    }

    

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop composite index
            $table->dropUnique(['email', 'provider']);

            // Restore unique email (if needed)
            $table->unique('email');
        });
    }
};
