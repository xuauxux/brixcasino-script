<?php

namespace App\Http\Controllers\Minecraft;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Minecraft\Economy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * In-game wallet pages: pay the bot to deposit, get paid back in game.
 * Every amount is created server-side; the bot only reports receipts.
 */
class WalletController extends Controller
{
    public function __construct(private readonly Economy $economy) {}

    public function depositPage()
    {
        $user = Auth::user();
        $this->economy->expireStale();

        $tiers = [];

        foreach (config('minecraft.deposit.tiers') as $tier) {
            $tiers[] = [
                'id' => $tier['id'],
                'name' => $tier['name'],
                'game' => (int) $tier['game'],
                'coins' => $this->economy->coinsFor((int) $tier['game']),
            ];
        }

        return view('deposit', [
            'verified' => $this->economy->verified($user),
            'ign' => $user->minecraft_ign,
            'bot' => $this->economy->botIgn(),
            'online' => $this->economy->bridgeOnline(),
            'enabled' => (bool) config('minecraft.deposit.enabled'),
            'min' => (int) config('minecraft.deposit.min_game'),
            'max' => (int) config('minecraft.deposit.max_game'),
            'coins_per_million' => (int) config('minecraft.coins_per_million'),
            'tiers' => $tiers,
            'pending' => $this->ownDeposits($user, ['pending']),
            'recent' => $this->ownDeposits($user, ['completed', 'expired']),
        ]);
    }

    public function depositCreate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->economy->createDepositRequest(Auth::user(), (int) $data['amount']);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function depositStatus(): JsonResponse
    {
        $user = Auth::user();
        $this->economy->expireStale();

        return response()->json([
            'verified' => $this->economy->verified($user),
            'ign' => $user->minecraft_ign,
            'online' => $this->economy->bridgeOnline(),
            'bot' => $this->economy->botIgn(),
            'balance' => (float) $user->balance,
            'pending' => $this->ownDeposits($user, ['pending']),
            'recent' => $this->ownDeposits($user, ['completed', 'expired']),
        ]);
    }

    public function withdrawPage()
    {
        $user = Auth::user();
        $this->economy->expireStale();

        $verified = $this->economy->verified($user);

        return view('withdraw', [
            'verified' => $verified,
            'ign' => $user->minecraft_ign,
            'bot' => $this->economy->botIgn(),
            'online' => $this->economy->bridgeOnline(),
            'enabled' => (bool) config('minecraft.withdraw.enabled'),
            'balance' => (float) $user->balance,
            'available' => $verified ? $this->economy->availableFor($user) : 0,
            'available_coins' => $this->economy->coinsFor((int) ($verified ? $this->economy->availableFor($user) : 0)),
            'min' => (int) config('minecraft.withdraw.min_game'),
            'max' => (int) config('minecraft.withdraw.max_game'),
            'tax_bps' => (int) config('minecraft.withdraw.tax_bps'),
            'coins_per_million' => (int) config('minecraft.coins_per_million'),
            'pending' => $this->ownWithdrawals($user, ['pending', 'sending']),
            'recent' => $this->ownWithdrawals($user, ['paid', 'failed', 'unconfirmed', 'cancelled', 'expired']),
        ]);
    }

    public function withdrawCreate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->economy->createWithdrawal(Auth::user(), (int) $data['amount']);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function withdrawCancel(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->economy->cancelWithdrawal(Auth::user(), (int) $data['id']);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function withdrawStatus(): JsonResponse
    {
        $user = Auth::user();
        $this->economy->expireStale();

        $verified = $this->economy->verified($user);

        return response()->json([
            'verified' => $verified,
            'ign' => $user->minecraft_ign,
            'online' => $this->economy->bridgeOnline(),
            'balance' => (float) $user->balance,
            'available' => $verified ? $this->economy->availableFor($user) : 0,
            'pending' => $this->ownWithdrawals($user, ['pending', 'sending']),
            'recent' => $this->ownWithdrawals($user, ['paid', 'failed', 'unconfirmed', 'cancelled', 'expired']),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function ownDeposits(User $user, array $statuses): array
    {
        $rows = DB::table('mc_deposit_requests')
            ->where('user_id', $user->id)
            ->whereIn('status', $statuses)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $out = [];

        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) $row->id,
                'game_amount' => (int) $row->game_amount,
                'coins' => (float) $row->coins,
                'status' => (string) $row->status,
                'at' => (string) $row->created_at,
                'expires_in' => max(0, now()->diffInSeconds($row->expires_at, false)),
            ];
        }

        return $out;
    }

    /** @return array<int, array<string, mixed>> */
    private function ownWithdrawals(User $user, array $statuses): array
    {
        $rows = DB::table('mc_withdrawals')
            ->where('user_id', $user->id)
            ->whereIn('status', $statuses)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $out = [];

        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) $row->id,
                'player' => (string) $row->player,
                'game_amount' => (int) $row->game_amount,
                'coins' => (float) $row->coins,
                'tax' => (int) $row->tax,
                'status' => (string) $row->status,
                'at' => (string) $row->created_at,
            ];
        }

        return $out;
    }
}
