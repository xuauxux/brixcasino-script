<?php

namespace App\Services\Platform;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * XP and levels. XP only ever grows, and every level threshold is larger than
 * the one before (quadratic), so raining points on the site cannot inflate it —
 * only recorded activity can.
 */
class Levels
{
    public const MAX_LEVEL = 100;

    /** Rewards paid once when a level is reached. */
    public const REWARDS = [
        2 => 2_500,
        3 => 10_000,
        5 => 50_000,
        7 => 150_000,
        10 => 500_000,
        15 => 1_500_000,
        20 => 4_000_000,
        30 => 12_000_000,
        50 => 40_000_000,
        75 => 100_000_000,
        100 => 250_000_000,
    ];

    /** Total XP needed to be at this level. */
    public static function threshold(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }

        return (int) (500 * (($level - 1) ** 2));
    }

    public static function levelFor(int $xp): int
    {
        $level = 1;

        while ($level < self::MAX_LEVEL && $xp >= self::threshold($level + 1)) {
            $level++;
        }

        return $level;
    }

    /** @return array{level: int, xp: int, floor: int, next: int, into: int, needed: int, percent: int} */
    public static function progress(?User $user): array
    {
        $xp = (int) ($user->xp ?? 0);
        $level = max(1, (int) ($user->level ?? self::levelFor($xp)));
        $floor = self::threshold($level);
        $next = self::threshold(min(self::MAX_LEVEL, $level + 1));
        $needed = max(1, $next - $floor);

        return [
            'level' => $level,
            'xp' => $xp,
            'floor' => $floor,
            'next' => $next,
            'into' => max(0, $xp - $floor),
            'needed' => $needed,
            'percent' => (int) min(100, round((($xp - $floor) / $needed) * 100)),
        ];
    }

    /** Adds XP and pays any level rewards the user just unlocked. */
    public static function awardXp(User $user, int $xp): int
    {
        if ($xp <= 0) {
            return (int) ($user->level ?? 1);
        }

        $current = (int) ($user->xp ?? 0);
        $from = (int) ($user->level ?? 1);
        $total = $current + $xp;
        $level = self::levelFor($total);

        DB::table('users')->where('id', $user->id)->update([
            'xp' => $total,
            'level' => $level,
            'last_activity_at' => now(),
        ]);

        $user->setAttribute('xp', $total);
        $user->setAttribute('level', $level);

        self::payLevels($user, $from, $level, $current);

        return $level;
    }

    /**
     * Rebuilds XP from recorded activity if the stored value lags behind, so a
     * missed hook can never lose a player's progress.
     */
    public static function sync(User $user): void
    {
        if (! Schema::hasTable('activity_events')) {
            return;
        }

        $derived = (int) DB::table('activity_events')->where('user_id', $user->id)->sum('xp');

        if ($derived > (int) ($user->xp ?? 0)) {
            DB::table('users')->where('id', $user->id)->update([
                'xp' => $derived,
                'level' => self::levelFor($derived),
            ]);

            $user->setAttribute('xp', $derived);
            $user->setAttribute('level', self::levelFor($derived));
        }
    }

    /** @return array<int, float> level => reward */
    public static function rewards(): array
    {
        return self::REWARDS;
    }

    /** The next few levels that still pay a reward. */
    public static function upcoming(int $level, int $count = 4): array
    {
        $out = [];

        foreach (self::REWARDS as $rewardLevel => $amount) {
            if ($rewardLevel > $level) {
                $out[$rewardLevel] = $amount;

                if (count($out) >= $count) {
                    break;
                }
            }
        }

        return $out;
    }

    private static function payLevels(User $user, int $from, int $to, int $xpBefore): void
    {
        if ($to <= $from) {
            return;
        }

        $wallet = new Wallet();

        foreach (self::REWARDS as $level => $amount) {
            if ($level <= $from || $level > $to) {
                continue;
            }

            $already = DB::table('level_claims')->where('user_id', $user->id)->where('level', $level)->exists();

            if ($already) {
                continue;
            }

            DB::table('level_claims')->insert([
                'user_id' => $user->id,
                'level' => $level,
                'reward' => $amount,
                'created_at' => now(),
            ]);

            if ($amount > 0) {
                $wallet->credit((int) $user->id, $amount, 'level', 'Level '.$level.' reward', ['level' => $level]);
            }
        }
    }
}
