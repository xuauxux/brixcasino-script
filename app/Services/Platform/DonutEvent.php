<?php

namespace App\Services\Platform;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The two-week DONUTTRIX letter event.
 *
 * Letters drop from recorded activity (games, rain, daily bonus). Missing
 * letters only are handed out, so the word can be completed but never
 * duplicated; a completion pays 100,000,000 exactly once because
 * event_rewards.user_id is unique.
 */
class DonutEvent
{
    public const WORD = 'DONUTTRIX';

    public const REWARD = 100_000_000;

    public const DURATION_DAYS = 14;

    /** Letter drop chance in percent for one activity action. */
    public const DROP_CHANCE = 18;

    /** Starts the 14-day clock the first time it is asked for. */
    public static function ensureWindow(): array
    {
        $window = Settings::eventWindow();

        if ($window['start'] === null || $window['end'] === null) {
            $start = Carbon::now();
            $end = $start->copy()->addDays(self::DURATION_DAYS);

            Settings::set('event_start', $start->toDateTimeString());
            Settings::set('event_end', $end->toDateTimeString());

            return ['start' => $start->toDateTimeString(), 'end' => $end->toDateTimeString()];
        }

        return $window;
    }

    /** @return array{active: bool, starts_at: ?Carbon, ends_at: ?Carbon, seconds_left: int, progress: int} */
    public static function status(): array
    {
        $window = self::ensureWindow();
        $start = $window['start'] !== null ? Carbon::parse($window['start']) : null;
        $end = $window['end'] !== null ? Carbon::parse($window['end']) : null;
        $now = Carbon::now();

        $total = ($start && $end) ? max(1, $start->diffInSeconds($end)) : 1;
        $left = $end !== null ? max(0, $now->diffInSeconds($end, false)) : 0;

        return [
            'active' => $start !== null && $end !== null && $now->between($start, $end),
            'starts_at' => $start,
            'ends_at' => $end,
            'seconds_left' => (int) $left,
            'progress' => (int) min(100, round((($total - $left) / $total) * 100)),
        ];
    }

    /** @return array<int, string> letters the player already owns */
    public static function collected(int $userId): array
    {
        if (! Schema::hasTable('event_letters')) {
            return [];
        }

        return DB::table('event_letters')
            ->where('user_id', $userId)
            ->pluck('letter')
            ->map(fn ($letter) => strtoupper((string) $letter))
            ->all();
    }

    /** @return array{letters: array<int, string>, missing: array<int, string>, count: int, complete: bool, rewarded: bool} */
    public static function progress(User $user): array
    {
        $letters = array_values(array_unique(array_intersect(str_split(self::WORD), self::collected((int) $user->id))));
        $missing = array_values(array_diff(str_split(self::WORD), $letters));

        // The word has nine tiles ("T" appears twice) but only eight distinct
        // letters, so the progress counter counts the tiles that are filled.
        $filled = array_values(array_filter(str_split(self::WORD), fn ($letter) => in_array($letter, $letters, true)));

        return [
            'letters' => $letters,
            'missing' => $missing,
            'count' => count($filled),
            'complete' => $missing === [],
            'rewarded' => Schema::hasTable('event_rewards')
                && DB::table('event_rewards')->where('user_id', $user->id)->exists(),
        ];
    }

    /** Rolls for a letter after an activity action. Returns the letter or null. */
    public function chance(User $user, string $source): ?string
    {
        try {
            if (! self::status()['active']) {
                return null;
            }

            if (random_int(1, 100) > self::DROP_CHANCE) {
                return null;
            }

            return $this->grant($user, $source);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Hands out one missing letter (random). Returns null when the word is done. */
    public function grant(User $user, string $source = 'game', ?string $letter = null): ?string
    {
        if (! Schema::hasTable('event_letters')) {
            return null;
        }

        $missing = array_values(array_diff(str_split(self::WORD), self::collected((int) $user->id)));

        if ($missing === []) {
            // The word is already complete (for example collected before this
            // fix shipped): pay the reward instead of silently doing nothing.
            $this->complete($user);

            return null;
        }

        $pick = $letter !== null && in_array(strtoupper($letter), $missing, true)
            ? strtoupper($letter)
            : $missing[array_rand($missing)];

        $inserted = DB::table('event_letters')->insertOrIgnore([
            'user_id' => $user->id,
            'letter' => $pick,
            'source' => mb_substr($source, 0, 32),
            'collected_at' => now(),
        ]);

        if ($inserted === 0) {
            return null;
        }

        (new Activity())->points($user, 200, 'event');

        if (array_diff(str_split(self::WORD), self::collected((int) $user->id)) === []) {
            $this->complete($user);
        }

        return $pick;
    }

    /** Pays the completion reward once. */
    public function complete(User $user): bool
    {
        if (! Schema::hasTable('event_rewards')) {
            return false;
        }

        if (DB::table('event_rewards')->where('user_id', $user->id)->exists()) {
            return false;
        }

        if (self::progress($user)['missing'] !== []) {
            return false;
        }

        DB::table('event_rewards')->insertOrIgnore([
            'user_id' => $user->id,
            'amount' => self::REWARD,
            'awarded_at' => now(),
        ]);

        (new Wallet())->credit((int) $user->id, self::REWARD, 'event', 'DONUTTRIX completed');

        return true;
    }

    /** Most letters collected, for the event leaderboard. */
    public static function leaderboard(int $limit = 10): array
    {
        if (! Schema::hasTable('event_letters')) {
            return [];
        }

        $rows = DB::table('event_letters')
            ->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as letters'), DB::raw('MIN(collected_at) as first_at'))
            ->orderByDesc('letters')
            ->orderBy('first_at')
            ->limit($limit)
            ->get();

        $names = User::whereIn('id', $rows->pluck('user_id'))->pluck('username', 'id');
        $out = [];

        foreach ($rows as $i => $row) {
            $out[] = [
                'rank' => $i + 1,
                'user_id' => (int) $row->user_id,
                'username' => (string) ($names[$row->user_id] ?? 'player'.$row->user_id),
                'letters' => (int) $row->letters,
            ];
        }

        return $out;
    }
}
