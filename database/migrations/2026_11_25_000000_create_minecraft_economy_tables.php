<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * In-game economy: IGN verification codes, deposit requests matched against the
 * bot's /pay receipts, the payout queue and the bot event log used for dedupe.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'minecraft_ign')) {
                $table->string('minecraft_ign', 16)->nullable()->unique()->after('username');
            }
            if (! Schema::hasColumn('users', 'minecraft_verified_at')) {
                $table->timestamp('minecraft_verified_at')->nullable()->after('minecraft_ign');
            }
        });

        if (! Schema::hasTable('mc_link_codes')) {
            Schema::create('mc_link_codes', function (Blueprint $table) {
                $table->id();
                $table->string('ign', 16)->index();
                $table->string('code_hash', 64)->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('consumed_at')->nullable();
                $table->timestamp('expires_at')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mc_deposit_requests')) {
            Schema::create('mc_deposit_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('player', 16)->index();
                $table->unsignedBigInteger('game_amount');
                $table->decimal('coins', 14, 2);
                $table->string('status', 16)->default('pending')->index();
                $table->timestamp('expires_at')->index();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mc_withdrawals')) {
            Schema::create('mc_withdrawals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('player', 16)->index();
                $table->unsignedBigInteger('game_amount');
                $table->decimal('coins', 14, 2);
                $table->unsignedInteger('tax')->default(0);
                $table->string('status', 16)->default('pending')->index();
                $table->timestamp('expires_at')->index();
                $table->timestamp('claimed_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->string('note', 200)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mc_unmatched')) {
            Schema::create('mc_unmatched', function (Blueprint $table) {
                $table->id();
                $table->string('player', 16)->index();
                $table->unsignedBigInteger('game_amount');
                $table->string('raw', 300)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mc_events')) {
            Schema::create('mc_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_id', 64)->unique();
                $table->string('kind', 16);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('mc_events');
        Schema::dropIfExists('mc_unmatched');
        Schema::dropIfExists('mc_withdrawals');
        Schema::dropIfExists('mc_deposit_requests');
        Schema::dropIfExists('mc_link_codes');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'minecraft_verified_at')) {
                $table->dropColumn('minecraft_verified_at');
            }
            if (Schema::hasColumn('users', 'minecraft_ign')) {
                $table->dropColumn('minecraft_ign');
            }
        });
    }
};
