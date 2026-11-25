<?php

namespace App\Services\Platform;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Daily / weekly / monthly activity leaderboards.
 *
 * These are standings only: a player's score is the sum of recorded activity
 * plus the bets already stored by the older games, so nothing has to be
 * re-instrumented. Nothing is paid out for a rank — the boards are for
 * bragging rights.
 */
class Leaderboards
{
    public const PERIODS = ['daily', 'weekly', 'monthly'];

    /** The key that identifies the period currently running. */
    public static function key(string $period, ?Carbon $at = null): string
    {
        $at = $at ?? Carbon::now();

        switch ($period) {
            case 'daily':
                return $at->format('Y-m-d');
            case 'weekly':
                return $at->isoFormat('GGGG-[W]WW');
            default:
                return $at->format('Y-m');
        }
    }

    /** The key of the period that just ended. */
    public static function previousKey(string $period, ?Carbon $at = null): string
    {
        $at = $at ?? Carbon::now();

        switch ($period) {
            case 'daily':
                return $at->copy()->subDay()->format('Y-m-d');
            case 'weekly':
                return $at->copy()->subWeek()->isoFormat('GGGG-[W]WW');
            default:
                return $at->copy()->subMonthNoOverflow()->format('Y-m');
        }
    }

    /**
     * "2026-W48" is our weekly key. Carbon cannot parse it with
     * createFromFormat, so the year and week are read directly.
     */
    private static function weekStart(string $key): Carbon
    {
        if (preg_match('/^(\d{4})-W(\d{2})$/', $key, $m) === 1) {
            return Carbon::now()->setISODate((int) $m[1], (int) $m[2])->startOfWeek();
        }

        return Carbon::now()->startOfWeek();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public static function window(string $period, string $key): array
    {
        [$start, $end] = match ($period) {
            'daily' => [Carbon::createFromFormat('Y-m-d', $key)->startOfDay(), null],
            'weekly' => [self::weekStart($key), null],
            default => [Carbon::createFromFormat('Y-m', $key)->startOfMonth(), null],
        };

        $end = match ($period) {
            'daily' => $start->copy()->endOfDay(),
            'weekly' => $start->copy()->endOfWeek(),
            default => $start->copy()->endOfMonth(),
        };

        return [$start, $end];
    }

    /**
     * Top players for a period.
     *
     * @return array<int, array{user_id: int, username: string, score: int, rank: int}>
     */
    public static function top(string $period, int $limit = 10, ?string $key = null): array
    {
        $key = $key ?? self::key($period);
        [$from, $to] = self::window($period, $key);

        $totals = [];

        if (Schema::hasTable('activity_events')) {
            foreach (DB::table('activity_events')
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('user_id')
                ->select('user_id', DB::raw('SUM(points) as score'))
                ->get() as $row) {
                $totals[(int) $row->user_id] = ($totals[(int) $row->user_id] ?? 0) + (int) $row->score;
            }
        }

        // Bets kept by the games that predate the activity table.
        foreach ([
            ['dice', 'bet'],
            ['mines', 'bet'],
            ['wheel_bets', 'price'],
            ['jackpot_bets', 'sum'],
            ['crash_bets', 'price'],
        ] as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $rows = DB::table($table)
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('user_id')
                ->select('user_id', DB::raw('SUM('.$column.') as score'))
                ->get();

            foreach ($rows as $row) {
                $totals[(int) $row->user_id] = ($totals[(int) $row->user_id] ?? 0) + (int) round((float) $row->score);
            }
        }

        arsort($totals);

        $names = User::whereIn('id', array_slice(array_keys($totals), 0, max($limit, 1)))
            ->pluck('username', 'id');

        $out = [];
        $rank = 0;

        foreach ($totals as $userId => $score) {
            if ($score <= 0) {
                continue;
            }

            $rank++;
            $out[] = [
                'user_id' => (int) $userId,
                'username' => (string) ($names[$userId] ?? 'player'.$userId),
                'score' => (int) $score,
                'rank' => $rank,
            ];

            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /** Where a player sits in the running period (1-based, null when unranked). */
    public static function position(User $user, string $period): ?array
    {
        foreach (self::top($period, 100) as $row) {
            if ($row['user_id'] === (int) $user->id) {
                return $row;
            }
        }

        return null;
    }

    /** When the running period ends. */
    public static function endsAt(string $period): Carbon
    {
        return match ($period) {
            'daily' => Carbon::now()->endOfDay(),
            'weekly' => Carbon::now()->endOfWeek(),
            default => Carbon::now()->endOfMonth(),
        };
    }
}
