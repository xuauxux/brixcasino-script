<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Minecraft accounts players whisper to and pay. Admins add them from the
 * dashboard; verification accepts a whisper to any "all"/"verify" account, and
 * deposits are sent to an "all"/"deposit" one.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('mc_bot_accounts')) {
            Schema::create('mc_bot_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('ign', 16)->unique();
                $table->string('purpose', 16)->default('all'); // all|verify|deposit|withdraw
                $table->boolean('enabled')->default(true);
                $table->string('note', 120)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('mc_bot_accounts');
    }
};
