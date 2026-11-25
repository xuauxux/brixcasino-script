<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Rank is joined in the jackpot/leaderboard queries; the rest are view fields. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'rank')) {
                $table->unsignedInteger('rank')->default(0);
            }
            if (! Schema::hasColumn('users', 'demo_balance')) {
                $table->decimal('demo_balance', 14, 2)->default(0);
            }
            if (! Schema::hasColumn('users', 'photo_200')) {
                $table->string('photo_200')->nullable();
            }
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 64)->nullable();
            }
            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 64)->nullable();
            }
        });
    }

    public function down()
    {
        foreach (['rank', 'demo_balance', 'photo_200', 'first_name', 'last_name'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
