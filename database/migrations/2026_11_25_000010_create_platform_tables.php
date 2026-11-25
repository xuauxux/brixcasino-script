<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform layer: owner role, XP/levels, wallet ledger, activity scoring,
 * competitions, rain, the DONUTTRIX event and referrals.
 *
 * Everything hangs off the existing `users` table (balance, ref_code,
 * referred_by, admin, ban) so nothing in the current site has to change shape.
 */
return new class extends Migration
{
    public function up()
    {
        /* ------------------------------------------------------------ users */
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 16)->default('user')->after('admin'); // user|admin|owner
            }

            if (! Schema::hasColumn('users', 'xp')) {
                $table->unsignedBigInteger('xp')->default(0)->after('role');
            }

            if (! Schema::hasColumn('users', 'level')) {
                $table->unsignedInteger('level')->default(1)->after('xp');
            }

            if (! Schema::hasColumn('users', 'banned_at')) {
                $table->timestamp('banned_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'ban_reason')) {
                $table->string('ban_reason', 190)->nullable();
            }

            if (! Schema::hasColumn('users', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable();
            }
        });

        /* ------------------------------------------------------ key/values */
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->string('name', 64)->primary();
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        /* ---------------------------------------------------- wallet ledger */
        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('type', 24)->index(); // admin_add|admin_remove|level|leaderboard|rain|event|referral|game|deposit|withdraw
            $table->decimal('amount', 18, 2); // signed
            $table->decimal('balance_before', 18, 2)->default(0);
            $table->decimal('balance_after', 18, 2)->default(0);
            $table->string('reason', 190)->nullable();
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        /* -------------------------------------------------------- admin log */
        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->index();
            $table->string('action', 48)->index();
            $table->unsignedBigInteger('target_user_id')->nullable()->index();
            $table->string('details', 255)->nullable();
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        /* --------------------------------------------------------- activity */
        Schema::create('activity_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('xp')->default(0);
            $table->string('source', 32)->default('game');
            $table->timestamp('created_at')->nullable()->index();
            $table->index(['user_id', 'created_at']);
        });

        /* ----------------------------------------------------- competitions */
        Schema::create('leaderboard_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('period', 8); // daily|weekly|monthly
            $table->string('period_key', 12);
            $table->unsignedTinyInteger('rank');
            $table->unsignedBigInteger('user_id');
            $table->string('username', 64)->nullable();
            $table->unsignedBigInteger('score')->default(0);
            $table->decimal('reward', 18, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->unique(['period', 'period_key', 'rank']);
        });

        Schema::create('leaderboard_history', function (Blueprint $table) {
            $table->id();
            $table->string('period', 8);
            $table->string('period_key', 12);
            $table->unsignedTinyInteger('rank');
            $table->unsignedBigInteger('user_id');
            $table->string('username', 64)->nullable();
            $table->unsignedBigInteger('score')->default(0);
            $table->decimal('reward', 18, 2)->default(0);
            $table->timestamp('created_at')->nullable();
            $table->unique(['period', 'period_key', 'user_id']);
            $table->index(['period', 'period_key']);
        });

        /* -------------------------------------------------------------- rain */
        Schema::create('rain_events', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 18, 2);
            $table->string('status', 12)->default('running'); // running|finished|cancelled
            $table->unsignedBigInteger('started_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('participants')->default(0);
            $table->decimal('paid_out', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('rain_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rain_id')->index();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 18, 2)->default(0);
            $table->timestamp('joined_at')->nullable();
            $table->unique(['rain_id', 'user_id']);
        });

        /* ---------------------------------------------------- DONUTTRIX event */
        Schema::create('event_letters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->char('letter', 1);
            $table->string('source', 32)->default('game');
            $table->timestamp('collected_at')->nullable();
            $table->unique(['user_id', 'letter']);
        });

        Schema::create('event_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->decimal('amount', 18, 2)->default(0);
            $table->timestamp('awarded_at')->nullable();
        });

        /* -------------------------------------------------------- referrals */
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();   // referrer
            $table->unsignedBigInteger('referred_id')->unique(); // new account
            $table->decimal('amount', 18, 2)->default(0);
            $table->timestamp('created_at')->nullable();
        });

        /* ------------------------------------------------------ level claims */
        Schema::create('level_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedInteger('level');
            $table->decimal('reward', 18, 2)->default(0);
            $table->timestamp('created_at')->nullable();
            $table->unique(['user_id', 'level']);
        });
    }

    public function down()
    {
        foreach ([
            'level_claims', 'referral_rewards', 'event_rewards', 'event_letters',
            'rain_participants', 'rain_events', 'leaderboard_history',
            'leaderboard_payouts', 'activity_events', 'admin_logs',
            'balance_transactions', 'platform_settings',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
