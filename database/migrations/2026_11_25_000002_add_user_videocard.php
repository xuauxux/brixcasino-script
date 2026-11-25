<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** app.js fingerprints the client and PATCHes this onto the user row. */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('users', 'videocard')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('videocard')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'videocard')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('videocard');
            });
        }
    }
};
