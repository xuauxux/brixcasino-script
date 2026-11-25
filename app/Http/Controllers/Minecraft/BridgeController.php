<?php

namespace App\Http\Controllers\Minecraft;

use App\Http\Controllers\Controller;
use App\Services\Minecraft\Economy;
use App\Services\Platform\Settings as PlatformSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Server-to-server API used by minecraft-bridge/bridge.js.
 *
 * Every call is HMAC signed with MC_BRIDGE_SECRET: no session, no CSRF, and an
 * unsigned request is rejected before it can touch a wallet.
 */
class BridgeController extends Controller
{
    /** How far the bot's clock may drift from ours. */
    private const MAX_SKEW_MS = 30_000;

    /** "<player> paid you $1.5m" / "<player> sent you 250,000" */
    private const RECEIPT = '/(?<![A-Za-z0-9_])([A-Za-z0-9_]{3,16})\s+(?:paid|sent)\s+you\s+\$?\s*([0-9][0-9,]*(?:\.[0-9]+)?)\s*([kKmMbB])?/iu';

    public function __construct(private readonly Economy $economy) {}

    public function heartbeat(Request $request): JsonResponse
    {
        if (! $this->signed($request)) {
            return response()->json(['ok' => false, 'error' => 'bad_signature'], 401);
        }

        $data = $request->validate([
            'state' => ['required', 'string', 'in:online,connecting,offline'],
            'bot' => ['nullable', 'string', 'min:3', 'max:16'],
            'host' => ['nullable', 'string', 'max:120'],
            'players' => ['nullable', 'integer', 'min:0'],
            'detail' => ['nullable', 'string', 'max:200'],
            // Optional: the bot's own in-game balance, when it can read it.
            'balance' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! empty($data['bot'])) {
            $this->economy->rememberBot($data['bot']);
        }

        Cache::put('mc:bridge', [
            'state' => $data['state'],
            'bot' => $data['bot'] ?? null,
            'host' => $data['host'] ?? null,
            'players' => $data['players'] ?? null,
            'detail' => $data['detail'] ?? null,
            'balance' => isset($data['balance']) ? (int) $data['balance'] : null,
            'at' => now()->toIso8601String(),
        ], now()->addMinutes(10));

        // The owner panel can switch the bot off; the bridge reads this on every
        // heartbeat and disconnects (or comes back) without a restart.
        return response()->json([
            'ok' => true,
            'bot' => $this->economy->botIgn(),
            'enabled' => PlatformSettings::botEnabled(),
        ]);
    }

    /**
     * The bridge asks for the login details of the account it was started with.
     * Signed like every other bridge call — the password never reaches a browser.
     */
    public function account(Request $request): JsonResponse
    {
        if (! $this->signed($request)) {
            return response()->json(['ok' => false, 'error' => 'bad_signature'], 401);
        }

        $data = $request->validate([
            'ign' => ['required', 'string', 'min:3', 'max:16'],
        ]);

        $account = $this->economy->botCredentials($data['ign']);

        if ($account === null) {
            return response()->json(['ok' => false, 'error' => 'unknown_account'], 404);
        }

        return response()->json(['ok' => true, 'account' => $account]);
    }

    /** A player whispered one of the configured accounts. No code is needed. */
    public function link(Request $request): JsonResponse
    {
        if (! $this->signed($request)) {
            return response()->json(['ok' => false, 'error' => 'bad_signature'], 401);
        }

        $data = $request->validate([
            'eventId' => ['required', 'string', 'max:64'],
            'player' => ['required', 'string', 'min:3', 'max:16'],
            'code' => ['nullable', 'string', 'min:4', 'max:32'],
        ]);

        $result = $this->economy->completeLink($data['eventId'], $data['player'], $data['code'] ?? null);

        return response()->json(['ok' => $result['status'] === 'linked'] + $result);
    }

    /**
     * A /pay receipt. The bot sends either `player` + `amount` (in-game dollars)
     * or the raw system line, which is re-parsed here so a buggy bridge cannot
     * invent an amount.
     */
    public function deposit(Request $request): JsonResponse
    {
        if (! $this->signed($request)) {
            return response()->json(['ok' => false, 'error' => 'bad_signature'], 401);
        }

        $data = $request->validate([
            'eventId' => ['required', 'string', 'max:64'],
            'player' => ['nullable', 'string', 'min:3', 'max:16'],
            'amount' => ['nullable', 'integer', 'min:1'],
            'receipt' => ['nullable', 'string', 'max:300'],
        ]);

        $player = $data['player'] ?? null;
        $amount = isset($data['amount']) ? (int) $data['amount'] : 0;

        if (! empty($data['receipt'])) {
            $parsed = $this->parseReceipt($data['receipt']);

            if ($parsed === null) {
                return response()->json(['ok' => false, 'error' => 'unparsable_receipt'], 422);
            }

            $player = $parsed['player'];
            $amount = $parsed['amount'];
        }

        if ($player === null || $amount < 1) {
            return response()->json(['ok' => false, 'error' => 'missing_amount'], 422);
        }

        $result = $this->economy->handleDeposit($data['eventId'], $player, $amount, $data['receipt'] ?? null);

        return response()->json(['ok' => $result['status'] === 'credited'] + $result);
    }

    /** The bot asks for the next payout. Null means "nothing to do". */
    public function claimWithdrawal(Request $request): JsonResponse
    {
        if (! $this->signed($request)) {
            return response()->json(['ok' => false, 'error' => 'bad_signature'], 401);
        }

        $claim = $this->economy->claimWithdrawal();

        return response()->json(['ok' => true, 'withdrawal' => $claim]);
    }

    /** The bot reports the /pay result for a claimed payout. */
    public function withdrawalResult(Request $request): JsonResponse
    {
        if (! $this->signed($request)) {
            return response()->json(['ok' => false, 'error' => 'bad_signature'], 401);
        }

        $data = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'in:paid,failed,unconfirmed'],
            'tax' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:200'],
            // bridge.js reports what the server said, not a note.
            'receipt' => ['nullable', 'string', 'max:300'],
            'reason' => ['nullable', 'string', 'max:160'],
        ]);

        $note = $data['note'] ?? $data['reason'] ?? ($data['receipt'] ?? null);

        $result = $this->economy->completeWithdrawal(
            (int) $data['id'],
            $data['status'],
            isset($data['tax']) ? (int) $data['tax'] : null,
            $note,
        );

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /** Public chat mirror is not used on this site; accept and drop it. */
    public function chat(Request $request): JsonResponse
    {
        if (! $this->signed($request)) {
            return response()->json(['ok' => false, 'error' => 'bad_signature'], 401);
        }

        return response()->json(['ok' => true, 'stored' => 0]);
    }

    /** @return array{player: string, amount: int}|null */
    private function parseReceipt(string $text): ?array
    {
        $clean = trim((string) preg_replace(['/§./u', '/\s+/u'], ['', ' '], $text));

        if (! preg_match(self::RECEIPT, $clean, $m)) {
            return null;
        }

        $value = (float) str_replace(',', '', $m[2]);
        $multiplier = match (strtolower($m[3] ?? '')) {
            'k' => 1_000,
            'm' => 1_000_000,
            'b' => 1_000_000_000,
            default => 1,
        };

        $amount = (int) round($value * $multiplier);

        if ($amount < 1) {
            return null;
        }

        return ['player' => strtolower($m[1]), 'amount' => $amount];
    }

    /** HMAC-SHA256 over "<ms timestamp>.<raw body>". */
    private function signed(Request $request): bool
    {
        $secret = (string) config('minecraft.secret');

        if (strlen($secret) < 16) {
            return false;
        }

        $stamp = (string) $request->header('X-Timestamp', '');
        $signature = (string) $request->header('X-Signature', '');

        if ($stamp === '' || $signature === '' || ! ctype_digit($stamp)) {
            return false;
        }

        if (abs((int) (microtime(true) * 1000) - (int) $stamp) > self::MAX_SKEW_MS) {
            return false;
        }

        $expected = hash_hmac('sha256', $stamp.'.'.$request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
