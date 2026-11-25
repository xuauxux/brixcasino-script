<?php

namespace App\Services\Platform;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every balance movement goes through here: one DB transaction, a row-locked
 * read of the current balance, and an immutable ledger entry. Nothing trusts a
 * balance supplied by a request.
 */
class Wallet
{
    /** @return array{ok: bool, error?: string, balance?: float, transaction_id?: int} */
    public function change(
        int $userId,
        float $amount,
        string $type,
        ?int $adminId = null,
        ?string $reason = null,
        array $meta = [],
        ?string $ip = null
    ): array {
        $amount = round($amount, 2);

        if ($amount == 0.0) {
            return ['ok' => false, 'error' => 'Amount is zero.'];
        }

        return DB::transaction(function () use ($userId, $amount, $type, $adminId, $reason, $meta, $ip) {
            $row = DB::table('users')->where('id', $userId)->lockForUpdate()->first();

            if ($row === null) {
                return ['ok' => false, 'error' => 'User not found.'];
            }

            $before = round((float) $row->balance, 2);
            $after = round($before + $amount, 2);

            if ($after < 0) {
                return ['ok' => false, 'error' => 'Not enough balance.'];
            }

            DB::table('users')->where('id', $userId)->update(['balance' => $after]);

            $transactionId = 0;

            if (Schema::hasTable('balance_transactions')) {
                $transactionId = (int) DB::table('balance_transactions')->insertGetId([
                    'user_id' => $userId,
                    'admin_id' => $adminId,
                    'type' => $type,
                    'amount' => $amount,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'reason' => $reason !== null ? mb_substr($reason, 0, 190) : null,
                    'meta' => $meta === [] ? null : json_encode($meta),
                    'ip' => $ip,
                    'created_at' => now(),
                ]);
            }

            return ['ok' => true, 'balance' => $after, 'transaction_id' => $transactionId];
        });
    }

    public function credit(int $userId, float $amount, string $type, ?string $reason = null, array $meta = []): array
    {
        return $this->change($userId, abs($amount), $type, null, $reason, $meta);
    }

    public function debit(int $userId, float $amount, string $type, ?string $reason = null, array $meta = []): array
    {
        return $this->change($userId, -abs($amount), $type, null, $reason, $meta);
    }

    /** Platform totals: how much the bot has handed out and how much players hold. */
    public function totals(): array
    {
        $held = (float) DB::table('users')->sum('balance');

        $distributed = 0.0;
        $removed = 0.0;

        if (Schema::hasTable('balance_transactions')) {
            $distributed = (float) DB::table('balance_transactions')->where('amount', '>', 0)->sum('amount');
            $removed = abs((float) DB::table('balance_transactions')->where('amount', '<', 0)->sum('amount'));
        }

        return [
            'held' => round($held, 2),
            'distributed' => round($distributed, 2),
            'removed' => round($removed, 2),
            'net' => round($distributed - $removed, 2),
        ];
    }
}
