<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The base schema. The reference build shipped with an imported dump instead of
 * migrations, so a fresh install had no tables at all (verification could not
 * run). These are the tables the site's models and controllers actually read.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('username', 32)->unique();
                $table->string('password');
                $table->string('avatar')->nullable();
                $table->string('ip', 45)->nullable();
                $table->decimal('balance', 14, 2)->default(0);
                $table->decimal('wager', 14, 2)->default(0);
                $table->string('vk_id')->nullable();
                $table->string('tg_id')->nullable();
                $table->string('unique_id')->nullable();
                $table->boolean('admin')->default(false);
                $table->boolean('youtuber')->default(false);
                $table->string('ref_code')->nullable();
                $table->unsignedBigInteger('referred_by')->nullable();
                $table->boolean('ban')->default(false);
                $table->timestamp('bonus_time')->nullable();
                $table->string('api_token', 80)->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('domain')->nullable();
                $table->decimal('min_payment', 14, 2)->default(1);
                $table->decimal('max_payment', 14, 2)->default(100000);
                $table->decimal('min_withdraw', 14, 2)->default(1);
                $table->boolean('coin_enabled')->default(true);
                $table->boolean('crash_enabled')->default(true);
                $table->boolean('dice_enabled')->default(true);
                $table->boolean('jackpot_enabled')->default(true);
                $table->boolean('mines_enabled')->default(true);
                $table->boolean('wheel_enabled')->default(true);
                $table->boolean('tech_work')->default(false);
                $table->timestamps();
            });
        }

        if (DB::table('settings')->count() === 0) {
            DB::table('settings')->insert([
                'id' => 1,
                'domain' => 'donuttrix',
                'min_payment' => 1,
                'max_payment' => 100000,
                'min_withdraw' => 1,
                'coin_enabled' => 1,
                'crash_enabled' => 1,
                'dice_enabled' => 1,
                'jackpot_enabled' => 1,
                'mines_enabled' => 1,
                'wheel_enabled' => 1,
                'tech_work' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('system', 32)->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->unsignedTinyInteger('status')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('withdraws')) {
            Schema::create('withdraws', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('system', 32)->nullable();
                $table->string('number')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->unsignedTinyInteger('status')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('dice')) {
            Schema::create('dice', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->decimal('bet', 14, 2)->default(0);
                $table->decimal('coef', 10, 4)->default(0);
                $table->string('type', 16)->nullable();
                $table->decimal('win', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mines')) {
            Schema::create('mines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('bombs')->nullable();
                $table->decimal('bet', 14, 2)->default(0);
                $table->unsignedTinyInteger('mines')->default(0);
                $table->string('click')->nullable();
                $table->boolean('onOff')->default(false);
                $table->string('result', 16)->nullable();
                $table->unsignedInteger('step')->default(0);
                $table->decimal('win', 14, 2)->default(0);
                $table->boolean('can_open')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wheel')) {
            Schema::create('wheel', function (Blueprint $table) {
                $table->id();
                $table->string('winner_color', 16)->nullable();
                $table->decimal('price', 14, 2)->default(0);
                $table->unsignedTinyInteger('status')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wheel_bets')) {
            Schema::create('wheel_bets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('game_id')->nullable()->index();
                $table->decimal('price', 14, 2)->default(0);
                $table->string('color', 16)->nullable();
                $table->decimal('win', 14, 2)->default(0);
                $table->decimal('balance', 14, 2)->default(0);
                $table->decimal('win_sum', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('jackpot')) {
            Schema::create('jackpot', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('game_id')->nullable()->index();
                $table->unsignedBigInteger('winner_id')->nullable();
                $table->decimal('winner_chance', 10, 4)->default(0);
                $table->string('winner_ticket')->nullable();
                $table->decimal('winner_sum', 14, 2)->default(0);
                $table->string('winner_username')->nullable();
                $table->string('winner_avatar')->nullable();
                $table->string('hash')->nullable();
                $table->decimal('price', 14, 2)->default(0);
                $table->unsignedTinyInteger('status')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('jackpot_bets')) {
            Schema::create('jackpot_bets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('game_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->decimal('sum', 14, 2)->default(0);
                $table->string('from', 16)->nullable();
                $table->string('to', 16)->nullable();
                $table->string('color', 16)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('promocodes')) {
            Schema::create('promocodes', function (Blueprint $table) {
                $table->id();
                $table->string('type', 32)->nullable();
                $table->unsignedTinyInteger('status')->default(1);
                $table->decimal('sum', 14, 2)->default(0);
                $table->unsignedInteger('activate')->default(0);
                $table->unsignedInteger('activate_limit')->default(0);
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('promo_log')) {
            Schema::create('promo_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->decimal('sum', 14, 2)->default(0);
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('profit')) {
            Schema::create('profit', function (Blueprint $table) {
                $table->id();
                $table->string('game', 32)->nullable();
                $table->decimal('sum', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('crash')) {
            Schema::create('crash', function (Blueprint $table) {
                $table->id();
                $table->decimal('multiplier', 10, 4)->default(0);
                $table->decimal('profit', 14, 2)->default(0);
                $table->unsignedTinyInteger('status')->default(0);
                $table->string('hash')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('crash_bets')) {
            Schema::create('crash_bets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('round_id')->nullable()->index();
                $table->decimal('price', 14, 2)->default(0);
                $table->decimal('withdraw', 10, 4)->nullable();
                $table->decimal('won', 14, 2)->default(0);
                $table->unsignedTinyInteger('status')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('slots')) {
            Schema::create('slots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->string('slot_code')->nullable()->index();
                $table->string('title')->nullable();
                $table->string('provider')->nullable();
                $table->string('image')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        foreach ([
            'slots', 'crash_bets', 'crash', 'profit', 'promo_log', 'promocodes',
            'jackpot_bets', 'jackpot', 'wheel_bets', 'wheel', 'mines', 'dice',
            'withdraws', 'payments', 'settings', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
