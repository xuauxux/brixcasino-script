<?php

namespace App\Http\Controllers\Minecraft;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Minecraft\Economy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Sign up and log in with a Minecraft name only.
 *
 * The browser asks for a code, the player whispers it to the bridge bot on
 * DonutSMP, the bot reports it back signed, and the polling browser is logged
 * in. Until that whisper happens the IGN proves nothing.
 */
class IgnAuthController extends Controller
{
    public function __construct(private readonly Economy $economy) {}

    public function start(Request $request): JsonResponse
    {
        $ign = trim((string) $request->input('ign', ''));

        if (! preg_match('/^\.?[A-Za-z0-9_]{3,16}$/', $ign)) {
            return response()->json([
                'ok' => false,
                'error' => 'Enter your Minecraft name (3-16 letters, numbers, _ or a leading dot).',
            ], 422);
        }

        // Trusted names skip the whisper entirely: the account is verified and
        // signed in on submit. The list lives in config/donut.php.
        if (in_array(strtolower(ltrim($ign, '.')), (array) config('donut.instant_verify', []), true)) {
            $instant = $this->economy->verifyInstantly($ign, Auth::id());

            if (empty($instant['ok'])) {
                return response()->json($instant, 422);
            }

            $user = User::where('id', $instant['user_id'])->first();

            if ($user !== null && (Auth::id() === null || (int) Auth::id() !== (int) $user->id)) {
                $request->session()->regenerate();
                Auth::login($user, true);
            }

            return response()->json([
                'ok' => true,
                'instant' => true,
                'ign' => $user?->minecraft_ign ?? $ign,
                'redirect' => url('/'),
            ]);
        }

        // Say exactly what is missing instead of a generic failure.
        if (! Schema::hasTable('mc_link_codes') || ! Schema::hasColumn('users', 'minecraft_verified_at')) {
            return response()->json([
                'ok' => false,
                'error' => 'Verification is not set up: run "php artisan migrate" on the server.',
            ], 503);
        }

        try {
            $result = $this->economy->startLink($ign, Auth::id());
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'error' => 'Could not create the code (server error). Check storage/logs/laravel.log.',
            ], 500);
        }

        if (empty($result['ok'])) {
            return response()->json($result, 422);
        }

        $request->session()->put('mc_link_id', $result['id']);
        $result['bot'] = $this->economy->botIgn();
        $result['bridge_online'] = $this->economy->bridgeOnline();
        $result['bot_ready'] = ! empty($result['bot']);

        return response()->json($result);
    }

    public function status(Request $request): JsonResponse
    {
        if (! Schema::hasTable('mc_link_codes')) {
            return response()->json(['state' => 'setup', 'error' => 'Verification is not set up: run "php artisan migrate".'], 503);
        }

        $id = (int) $request->query('id', 0);
        $sessionId = (int) $request->session()->get('mc_link_id', 0);

        if ($id < 1 || $id !== $sessionId) {
            return response()->json(['state' => 'unknown'], 422);
        }

        $status = $this->economy->linkStatus($id);

        if ($status['state'] !== 'verified') {
            return response()->json($status);
        }

        $user = User::where('id', $status['user_id'])->first();

        if ($user === null) {
            return response()->json(['state' => 'unknown'], 422);
        }

        // The code is single-use: only this browser session can turn it into a
        // login, and only once.
        $this->economy->consumeLink($id);
        $request->session()->forget('mc_link_id');

        if (Auth::check()) {
            // Already signed in: this was a re-link from the wallet page, so the
            // session must not be swapped to a different account.
            return response()->json([
                'state' => 'verified',
                'ign' => $user->minecraft_ign,
                'linked' => (int) Auth::id() === (int) $user->id,
                'reload' => true,
            ]);
        }

        $request->session()->regenerate();
        Auth::login($user, true);

        return response()->json([
            'state' => 'verified',
            'ign' => $user->minecraft_ign,
            'redirect' => url('/'),
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $request->session()->forget('mc_link_id');

        return response()->json(['state' => 'cancelled']);
    }
}
