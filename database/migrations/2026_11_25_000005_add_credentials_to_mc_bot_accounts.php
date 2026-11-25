<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Login details for the accounts the mineflayer bridge runs. A Minecraft name
 * alone is not enough to join a premium server, so each pool entry can hold the
 * authentication mode plus the Microsoft email and an encrypted password.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('mc_bot_accounts')) {
            return;
        }

        Schema::table('mc_bot_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('mc_bot_accounts', 'auth')) {
                $table->string('auth', 16)->default('microsoft')->after('purpose');
            }

            if (! Schema::hasColumn('mc_bot_accounts', 'login')) {
                $table->string('login', 190)->nullable()->after('auth');
            }

            if (! Schema::hasColumn('mc_bot_accounts', 'password_enc')) {
                $table->text('password_enc')->nullable()->after('login');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('mc_bot_accounts')) {
            return;
        }

        Schema::table('mc_bot_accounts', function (Blueprint $table) {
            foreach (['auth', 'login', 'password_enc'] as $column) {
                if (Schema::hasColumn('mc_bot_accounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
