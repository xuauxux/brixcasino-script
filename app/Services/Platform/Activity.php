<?php

namespace App\Services\Platform;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Records what players do. One row per action carries both the leaderboard
 * points and the XP it earned, so scores and levels can always be rebuilt from
 * the table instead of trusting a counter in the browser.
 */
class Activity
{
    /** A single bet/round: the amount wagered is the activity score. */
    public function game(User $user, float $bet, string $game): void
    {
        $bet = max(0, round($bet, 2));

        if ($bet < 0.01) {
            return;
        }

        $this->record($user, (int) min(2_000_000_000, round($bet)), max(1, (int) round($bet / 100)), $game);
    }

    /** Non-game actions (daily bonus, chat, event letters…). */
    public function points(User $user, int $points, string $source, ?int $xp = null): void
    {
        $this->record($user, max(1, $points), $xp ?? max(1, (int) round($points / 100)), $source);
    }

    private function record(User $user, int $points, int $xp, string $source): void
    {
        // Never let bookkeeping break a bet.
        try {
            if (! Schema::hasTable('activity_events')) {
                return;
            }

            DB::table('activity_events')->insert([
                'user_id' => $user->id,
                'points' => $points,
                'xp' => $xp,
                'source' => mb_substr($source, 0, 32),
                'created_at' => now(),
            ]);

            DB::table('users')->where('id', $user->id)->update(['last_activity_at' => now()]);

            Levels::awardXp($user, $xp);

            (new DonutEvent())->chance($user, $source);
            (new Referrals())->qualify($user);
        } catch (\Throwable $e) {
            logger()->warning('activity failed: '.$e->getMessage());
        }
    }

    /** Recent activity for the dashboard. */
    public function recent(int $userId, int $limit = 8): array
    {
        if (! Schema::hasTable('activity_events')) {
            return [];
        }

        return DB::table('activity_events')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['points', 'xp', 'source', 'created_at'])
            ->all();
    }
}
