<?php

namespace App\Http\Controllers\Minecraft;

use App\Http\Controllers\Controller;
use App\Services\Minecraft\Economy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Admin view of the in-game economy: every deposit, every payout, the receipts
 * the bot could not match, and the money the bot should be holding right now.
 */
class AdminPanelController extends Controller
{
    private const LIST_LIMIT = 100;

    public function __construct(private readonly Economy $economy) {}

    public function index()
    {
        $this->economy->expireStale();

        return view('admin.minecraft', [
            'summary' => $this->summary(),
            'bridge' => $this->bridge(),
            'accounts' => $this->economy->botAccounts(),
            'deposits' => $this->deposits(),
            'withdrawals' => $this->withdrawals(),
            'unmatched' => $this->unmatched(),
        ]);
    }

    /* ------------------------------------------------------- bot accounts */

    /** Adds a Minecraft account the site uses for verification, deposits and payouts. */
    public function storeAccount(Request $request)
    {
        $data = $request->validate([
            'ign' => ['required', 'string', 'min:3', 'max:16'],
            'purpose' => ['nullable', 'string', 'in:all,verify,deposit,withdraw'],
            'note' => ['nullable', 'string', 'max:120'],
            'auth' => ['nullable', 'string', 'in:microsoft,offline'],
            'login' => ['nullable', 'string', 'max:190'],
            'password' => ['nullable', 'string', 'max:190'],
        ]);

        $result = $this->economy->addBotAccount(
            $data['ign'],
            $data['purpose'] ?? 'all',
            $data['note'] ?? null,
            $data['auth'] ?? 'microsoft',
            $data['login'] ?? null,
            $data['password'] ?? null,
        );

        return redirect('/admin/minecraft')->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok'] ? 'Bot account saved.' : ($result['error'] ?? 'Could not save the account.'),
        );
    }

    public function toggleAccount(int $id)
    {
        $this->economy->toggleBotAccount($id);

        return redirect('/admin/minecraft')->with('success', 'Bot account updated.');
    }

    public function deleteAccount(int $id)
    {
        $this->economy->deleteBotAccount($id);

        return redirect('/admin/minecraft')->with('success', 'Bot account removed.');
    }

    /** Polled by the page so the float and the queue stay live. */
    public function live(): JsonResponse
    {
        $this->economy->expireStale();

        return response()->json([
            'ok' => true,
            'summary' => $this->summary(),
            'bridge' => $this->bridge(),
        ]);
    }

    /** @return array<string, mixed> */
    private function summary(): array
    {
        $float = $this->economy->floatSummary();
        $perMillion = (int) config('minecraft.coins_per_million', 100);

        return $float + [
            'coins_per_million' => $perMillion,
            'coins_available' => round($float['available'] / 1_000_000 * $perMillion, 2),
            'pending_deposits' => (int) DB::table('mc_deposit_requests')->where('status', 'pending')->count(),
            'pending_withdrawals' => (int) DB::table('mc_withdrawals')->where('status', 'pending')->count(),
            'sending' => (int) DB::table('mc_withdrawals')->where('status', 'sending')->count(),
            'unconfirmed' => (int) DB::table('mc_withdrawals')->where('status', 'unconfirmed')->count(),
            'unmatched' => (int) DB::table('mc_unmatched')->count(),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function bridge(): array
    {
        $state = Cache::get('mc:bridge');

        return [
            'online' => $this->economy->bridgeOnline(),
            'bot' => $this->economy->botIgn(),
            'state' => $state['state'] ?? 'offline',
            'host' => $state['host'] ?? null,
            'players' => $state['players'] ?? null,
            'detail' => $state['detail'] ?? null,
            'balance' => $state['balance'] ?? null,
            'at' => $state['at'] ?? null,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function deposits(): array
    {
        $rows = DB::table('mc_deposit_requests as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.user_id')
            ->orderByDesc('d.id')
            ->limit(self::LIST_LIMIT)
            ->get(['d.id', 'd.user_id', 'd.player', 'd.game_amount', 'd.coins', 'd.status', 'd.created_at', 'd.expires_at', 'u.username']);

        $out = [];

        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) $row->id,
                'user_id' => (int) $row->user_id,
                'username' => $row->username,
                'player' => (string) $row->player,
                'game_amount' => (int) $row->game_amount,
                'coins' => (float) $row->coins,
                'status' => (string) $row->status,
                'created_at' => (string) $row->created_at,
                'expires_at' => (string) $row->expires_at,
            ];
        }

        return $out;
    }

    /** @return array<int, array<string, mixed>> */
    private function withdrawals(): array
    {
        $rows = DB::table('mc_withdrawals as w')
            ->leftJoin('users as u', 'u.id', '=', 'w.user_id')
            ->orderByDesc('w.id')
            ->limit(self::LIST_LIMIT)
            ->get(['w.id', 'w.user_id', 'w.player', 'w.game_amount', 'w.coins', 'w.tax', 'w.status', 'w.note', 'w.created_at', 'w.completed_at', 'u.username']);

        $out = [];

        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) $row->id,
                'user_id' => (int) $row->user_id,
                'username' => $row->username,
                'player' => (string) $row->player,
                'game_amount' => (int) $row->game_amount,
                'coins' => (float) $row->coins,
                'tax' => (int) $row->tax,
                'status' => (string) $row->status,
                'note' => $row->note,
                'created_at' => (string) $row->created_at,
                'completed_at' => $row->completed_at !== null ? (string) $row->completed_at : null,
            ];
        }

        return $out;
    }

    /** @return array<int, array<string, mixed>> */
    private function unmatched(): array
    {
        return DB::table('mc_unmatched')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'player', 'game_amount', 'raw', 'created_at'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'player' => (string) $row->player,
                'game_amount' => (int) $row->game_amount,
                'raw' => (string) ($row->raw ?? ''),
                'created_at' => (string) $row->created_at,
            ])
            ->all();
    }
}
