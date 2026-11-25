<?php

namespace App\Services\Platform;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Referrals. Each account has a unique code, a new player can be attached to it
 * once, and the 20,000,000 is only paid after the invited player verifies their
 * IGN, reaches level 2 and does not share the referrer's IP — which is what
 * stops a farm of throwaway accounts.
 */
class Referrals
{
    public const REWARD = 20_000_000;

    public const MIN_LEVEL = 2;

    public static function code(User $user): string
    {
        if (! empty($user->ref_code)) {
            return (string) $user->ref_code;
        }

        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('ref_code', $code)->exists());

        DB::table('users')->where('id', $user->id)->update(['ref_code' => $code]);
        $user->setAttribute('ref_code', $code);

        return $code;
    }

    public static function link(User $user): string
    {
        return url('/parther/'.self::code($user));
    }

    /** Attaches a brand new account to the owner of a code (never to itself). */
    public static function attach(User $newUser, string $code): bool
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return false;
        }

        // Codes are stored exactly as created (legacy hex codes are lower-case),
        // so match without caring about the case the visitor typed.
        $referrer = User::whereRaw('UPPER(ref_code) = ?', [$code])->first();

        if ($referrer === null || (int) $referrer->id === (int) $newUser->id) {
            return false;
        }

        if ((int) $newUser->referred_by > 0) {
            return false;
        }

        // Same-IP accounts are not referrals.
        if ($referrer->ip !== null && $newUser->ip !== null && $referrer->ip === $newUser->ip) {
            return false;
        }

        DB::table('users')->where('id', $newUser->id)->update(['referred_by' => $referrer->id]);
        $newUser->setAttribute('referred_by', $referrer->id);

        return true;
    }

    /**
     * Pays the referrer as soon as the invited player qualifies. Safe to call on
     * every action: (referred_id) is unique in referral_rewards.
     */
    public function qualify(User $referred): void
    {
        try {
            if (! Schema::hasTable('referral_rewards')) {
                return;
            }

            $referrerId = (int) ($referred->referred_by ?? 0);

            if ($referrerId <= 0 || $referrerId === (int) $referred->id) {
                return;
            }

            if (empty($referred->minecraft_verified_at)) {
                return;
            }

            if ((int) ($referred->level ?? 1) < self::MIN_LEVEL) {
                return;
            }

            if (DB::table('referral_rewards')->where('referred_id', $referred->id)->exists()) {
                return;
            }

            $referrer = User::find($referrerId);

            if ($referrer === null || (int) $referrer->ban === 1) {
                return;
            }

            if ($referrer->ip !== null && $referred->ip !== null && $referrer->ip === $referred->ip) {
                return;
            }

            $inserted = DB::table('referral_rewards')->insertOrIgnore([
                'user_id' => $referrerId,
                'referred_id' => $referred->id,
                'amount' => self::REWARD,
                'created_at' => now(),
            ]);

            if ($inserted === 0) {
                return;
            }

            (new Wallet())->credit($referrerId, self::REWARD, 'referral', 'Referral: '.$referred->username, [
                'referred_id' => $referred->id,
            ]);
        } catch (\Throwable $e) {
            logger()->warning('referral failed: '.$e->getMessage());
        }
    }

    /** @return array{code: string, link: string, total: int, qualified: int, earned: float, history: array} */
    public static function stats(User $user): array
    {
        $total = User::where('referred_by', $user->id)->count();
        $qualified = Schema::hasTable('referral_rewards')
            ? DB::table('referral_rewards')->where('user_id', $user->id)->count()
            : 0;
        $earned = Schema::hasTable('referral_rewards')
            ? (float) DB::table('referral_rewards')->where('user_id', $user->id)->sum('amount')
            : 0.0;

        $history = Schema::hasTable('referral_rewards')
            ? DB::table('referral_rewards as r')
                ->leftJoin('users as u', 'u.id', '=', 'r.referred_id')
                ->where('r.user_id', $user->id)
                ->orderByDesc('r.id')
                ->limit(20)
                ->get(['r.amount', 'r.created_at', 'u.username', 'r.referred_id'])
                ->all()
            : [];

        return [
            'code' => self::code($user),
            'link' => self::link($user),
            'total' => $total,
            'qualified' => $qualified,
            'earned' => $earned,
            'history' => $history,
        ];
    }

    /** @return array<int, array{rank: int, username: string, referrals: int}> */
    public static function top(int $limit = 10): array
    {
        $rows = User::where('referred_by', '>', 0)
            ->groupBy('referred_by')
            ->select('referred_by', DB::raw('COUNT(*) as referrals'))
            ->orderByDesc('referrals')
            ->limit($limit)
            ->get();

        $names = User::whereIn('id', $rows->pluck('referred_by'))->pluck('username', 'id');
        $out = [];

        foreach ($rows as $i => $row) {
            $out[] = [
                'rank' => $i + 1,
                'user_id' => (int) $row->referred_by,
                'username' => (string) ($names[$row->referred_by] ?? 'player'.$row->referred_by),
                'referrals' => (int) $row->referrals,
            ];
        }

        return $out;
    }
}
