<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Settings as SiteSettings;
use App\Models\User;
use App\Services\Minecraft\Economy;
use App\Services\Platform\Audit;
use App\Services\Platform\Catalogue;
use App\Services\Platform\DonutEvent;
use App\Services\Platform\Leaderboards;
use App\Services\Platform\Levels;
use App\Services\Platform\Rains;
use App\Services\Platform\Referrals;
use App\Services\Platform\Settings;
use App\Services\Platform\Wallet;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Owner panel: people, money, bot, rain, maintenance and the competition engines.
 * Every mutation writes to the ledger and the admin log.
 */
class OwnerPanelController extends Controller
{
    public function __construct(
        private readonly Wallet $wallet,
        private readonly Rains $rains,
        private readonly Economy $economy,
    ) {}

    /* ------------------------------------------------------------ overview */

    public function overview()
    {
        $users = (int) User::count();
        $newToday = (int) User::where('created_at', '>=', Carbon::today())->count();
        $online = (int) User::where('last_activity_at', '>=', Carbon::now()->subMinutes(10))->count();
        $totals = $this->wallet->totals();

        return view('panel.overview', [
            'title' => 'Overview',
            'tab' => 'overview',
            'stats' => [
                'users' => $users,
                'new_today' => $newToday,
                'online' => $online,
                'held' => $totals['held'],
                'distributed' => $totals['distributed'],
                'removed' => $totals['removed'],
                'next_rain' => $this->nextRain(),
                'maintenance' => Settings::maintenance(),
            ],
            'bot' => $this->botState(),
            'rain' => $this->rains->active(),
            'topDaily' => Leaderboards::top('daily', 5),
            'topWeekly' => Leaderboards::top('weekly', 5),
            'recent' => DB::table('balance_transactions as t')
                ->leftJoin('users as u', 'u.id', '=', 't.user_id')
                ->leftJoin('users as a', 'a.id', '=', 't.admin_id')
                ->orderByDesc('t.id')->limit(8)
                ->get(['t.*', 'u.username', 'a.username as admin_name']),
            'logs' => Audit::recent(8),
        ]);
    }

    /* --------------------------------------------------------------- users */

    public function users(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = User::query();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('id', $q)
                    ->orWhere('username', 'like', '%'.$q.'%')
                    ->orWhere('minecraft_ign', 'like', '%'.$q.'%')
                    ->orWhere('ref_code', 'like', '%'.$q.'%');
            });
        }

        $users = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('panel.users', [
            'title' => 'Users',
            'tab' => 'users',
            'users' => $users,
            'q' => $q,
        ]);
    }

    public function user(int $id)
    {
        $user = User::findOrFail($id);
        Levels::sync($user);

        return view('panel.user', [
            'title' => $user->username,
            'tab' => 'users',
            'user' => $user,
            'progress' => Levels::progress($user),
            'referrals' => Referrals::stats($user),
            'event' => DonutEvent::progress($user),
            'transactions' => DB::table('balance_transactions as t')
                ->leftJoin('users as a', 'a.id', '=', 't.admin_id')
                ->where('t.user_id', $id)->orderByDesc('t.id')->limit(40)
                ->get(['t.*', 'a.username as admin_name']),
            'activity' => DB::table('activity_events')->where('user_id', $id)->orderByDesc('id')->limit(15)->get(),
        ]);
    }

    public function balance(Request $request, int $id)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000000000'],
            'mode' => ['required', 'in:add,remove'],
            'reason' => ['nullable', 'string', 'max:190'],
        ]);

        $user = User::findOrFail($id);
        $amount = (float) $data['amount'];
        $isAdd = $data['mode'] === 'add';

        $result = $this->wallet->change(
            (int) $user->id,
            $isAdd ? $amount : -$amount,
            $isAdd ? 'admin_add' : 'admin_remove',
            (int) $request->user()->id,
            $data['reason'] ?? ($isAdd ? 'Added by staff' : 'Removed by staff'),
            [],
            $request->ip(),
        );

        if (! $result['ok']) {
            return back()->with('error', $result['error'] ?? 'Could not change the balance.');
        }

        Audit::log(
            (int) $request->user()->id,
            $isAdd ? 'balance_add' : 'balance_remove',
            (int) $user->id,
            ($isAdd ? '+' : '-').number_format($amount).' → '.$user->username.($data['reason'] ? ' ('.$data['reason'].')' : ''),
            ['amount' => $amount, 'balance_after' => $result['balance']],
            $request->ip(),
        );

        return back()->with('success', ($isAdd ? 'Added ' : 'Removed ').number_format($amount).' to '.$user->username.'.');
    }

    public function setRole(Request $request, int $id)
    {
        $data = $request->validate(['role' => ['required', 'in:user,admin,owner']]);
        $user = User::findOrFail($id);

        if ((int) $user->id === (int) $request->user()->id && $data['role'] !== 'owner') {
            return back()->with('error', 'You cannot demote your own owner account.');
        }

        $user->forceFill([
            'role' => $data['role'],
            'admin' => $data['role'] === 'user' ? 0 : 1,
        ])->save();

        Audit::log((int) $request->user()->id, 'role_change', (int) $user->id, $user->username.' → '.$data['role'], [], $request->ip());

        return back()->with('success', $user->username.' is now '.$data['role'].'.');
    }

    public function ban(Request $request, int $id)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:190']]);
        $user = User::findOrFail($id);

        $user->forceFill(['ban' => 1, 'banned_at' => now(), 'ban_reason' => $data['reason'] ?? null])->save();

        Audit::log((int) $request->user()->id, 'ban', (int) $user->id, $data['reason'] ?? 'banned', [], $request->ip());

        return back()->with('success', $user->username.' is banned.');
    }

    public function unban(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $user->forceFill(['ban' => 0, 'banned_at' => null, 'ban_reason' => null])->save();

        Audit::log((int) $request->user()->id, 'unban', (int) $user->id, $user->username, [], $request->ip());

        return back()->with('success', $user->username.' is unbanned.');
    }

    /* -------------------------------------------------------- transactions */

    public function transactions(Request $request)
    {
        $type = (string) $request->get('type', '');
        $q = trim((string) $request->get('q', ''));
        $from = (string) $request->get('from', '');
        $to = (string) $request->get('to', '');

        $query = DB::table('balance_transactions as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->leftJoin('users as a', 'a.id', '=', 't.admin_id');

        if ($type !== '') {
            $query->where('t.type', $type);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('u.username', 'like', '%'.$q.'%')->orWhere('t.user_id', $q);
            });
        }

        if ($from !== '') {
            $query->where('t.created_at', '>=', Carbon::parse($from)->startOfDay());
        }

        if ($to !== '') {
            $query->where('t.created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        return view('panel.transactions', [
            'title' => 'Balance history',
            'tab' => 'transactions',
            'rows' => $query->orderByDesc('t.id')->paginate(25)->withQueryString(),
            'types' => DB::table('balance_transactions')->distinct()->orderBy('type')->pluck('type'),
            'filters' => compact('type', 'q', 'from', 'to'),
            'totals' => [
                'in' => (float) DB::table('balance_transactions')->where('amount', '>', 0)->sum('amount'),
                'out' => abs((float) DB::table('balance_transactions')->where('amount', '<', 0)->sum('amount')),
            ],
        ]);
    }

    public function botBalance()
    {
        $totals = $this->wallet->totals();
        $economy = $this->economy->floatSummary();

        return view('panel.bot', [
            'title' => 'Bot balance',
            'tab' => 'bot',
            'totals' => $totals,
            'float' => $economy,
            'bot' => $this->botState(),
            'rows' => DB::table('balance_transactions as t')
                ->leftJoin('users as u', 'u.id', '=', 't.user_id')
                ->whereIn('t.type', ['rain', 'leaderboard', 'level', 'event', 'referral', 'admin_add', 'admin_remove'])
                ->orderByDesc('t.id')->limit(30)
                ->get(['t.*', 'u.username']),
        ]);
    }

    /* ----------------------------------------------------------------- bot */

    public function botToggle(Request $request)
    {
        $enable = ! Settings::botEnabled();
        Settings::setBotEnabled($enable);

        Audit::log((int) $request->user()->id, $enable ? 'bot_on' : 'bot_off', null, 'Bot '.($enable ? 'enabled' : 'disabled'), [], $request->ip());

        return back()->with('success', 'Bot switched '.($enable ? 'ON' : 'OFF').'. The bridge picks it up on its next heartbeat.');
    }

    /* ---------------------------------------------------------------- games */

    public function games()
    {
        return view('panel.games', [
            'title' => 'Games',
            'tab' => 'games',
            'games' => Catalogue::all(),
        ]);
    }

    public function toggleGame(Request $request, string $game)
    {
        $catalogue = Catalogue::all();

        if (! isset($catalogue[$game])) {
            abort(404);
        }

        $setting = $catalogue[$game]['setting'];

        if ($setting === null) {
            return back()->with('error', 'That game has no availability switch.');
        }

        $row = SiteSettings::where('id', 1)->first();
        $current = (int) ($row->{$setting} ?? 0);

        SiteSettings::where('id', 1)->update([$setting => $current === 0 ? 1 : 0]);

        Audit::log((int) $request->user()->id, 'game_toggle', null, $game.' → '.($current === 0 ? 'disabled' : 'enabled'), [], $request->ip());

        return back()->with('success', $catalogue[$game]['name'].' is now '.($current === 0 ? 'disabled' : 'available').'.');
    }

    /* ----------------------------------------------------------------- rain */

    public function rain(Request $request)
    {
        $this->rains->tick();

        return view('panel.rain', [
            'title' => 'Rain',
            'tab' => 'rain',
            'settings' => Settings::rain(),
            'active' => $this->rains->active(),
            'history' => $this->rains->history(12),
            'next' => $this->nextRain(),
        ]);
    }

    public function rainSettings(Request $request)
    {
        $data = $request->validate([
            'interval' => ['required', 'integer', 'min:1', 'max:1440'],
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000000000'],
            'duration' => ['required', 'integer', 'min:15', 'max:3600'],
            'enabled' => ['nullable', 'in:1'],
        ]);

        Settings::set('rain_interval_minutes', (int) $data['interval']);
        Settings::set('rain_amount', (float) $data['amount']);
        Settings::set('rain_duration_seconds', (int) $data['duration']);
        Settings::set('rain_enabled', $request->has('enabled'));

        Audit::log((int) $request->user()->id, 'rain_settings', null, 'interval '.$data['interval'].'m / pot '.$data['amount'], [], $request->ip());

        return back()->with('success', 'Rain settings saved.');
    }

    public function rainStart(Request $request)
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:1', 'max:1000000000000'],
            'duration' => ['nullable', 'integer', 'min:15', 'max:3600'],
        ]);

        $result = $this->rains->start(
            (int) $request->user()->id,
            isset($data['amount']) ? (float) $data['amount'] : null,
            isset($data['duration']) ? (int) $data['duration'] : null,
        );

        if ($result['ok']) {
            Audit::log((int) $request->user()->id, 'rain_start', null, 'pot '.$result['rain']->amount, [], $request->ip());
        }

        return back()->with($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Rain started.' : ($result['error'] ?? 'Could not start the rain.'));
    }

    public function rainStop(Request $request)
    {
        $result = $this->rains->stop();

        if ($result['ok']) {
            Audit::log((int) $request->user()->id, 'rain_stop', null, 'paid '.$result['amount'].' to '.$result['participants'], [], $request->ip());
        }

        return back()->with($result['ok'] ? 'success' : 'error', $result['ok']
            ? 'Rain finished — '.number_format($result['amount']).' split between '.$result['participants'].' players.'
            : ($result['error'] ?? 'Could not stop the rain.'));
    }

    /* ---------------------------------------------------------- maintenance */

    public function maintenance()
    {
        return view('panel.maintenance', [
            'title' => 'Maintenance',
            'tab' => 'maintenance',
            'on' => Settings::maintenance(),
            'message' => Settings::maintenanceMessage(),
        ]);
    }

    public function maintenanceSave(Request $request)
    {
        $data = $request->validate([
            'state' => ['required', 'in:on,off'],
            'message' => ['nullable', 'string', 'max:190'],
        ]);

        $on = $data['state'] === 'on';
        Settings::setMaintenance($on, $data['message'] ?? null);

        Audit::log((int) $request->user()->id, $on ? 'maintenance_on' : 'maintenance_off', null, $data['message'] ?? null, [], $request->ip());

        return back()->with('success', 'Maintenance mode is '.($on ? 'ON — only the owner can use the site.' : 'off.'));
    }

    /* ------------------------------------------------------------ settings */

    public function settings()
    {
        return view('panel.settings', [
            'title' => 'Settings',
            'tab' => 'settings',
            'site' => SiteSettings::where('id', 1)->first(),
            'event' => DonutEvent::status(),
            'levels' => Levels::rewards(),
        ]);
    }

    public function settingsSave(Request $request)
    {
        $data = $request->validate([
            'min_payment' => ['required', 'numeric', 'min:0'],
            'min_withdraw' => ['required', 'numeric', 'min:0'],
            'maintenance_message' => ['nullable', 'string', 'max:190'],
            'admin_igns' => ['nullable', 'string', 'max:190'],
        ]);

        SiteSettings::where('id', 1)->update([
            'min_payment' => $data['min_payment'],
            'min_withdraw' => $data['min_withdraw'],
        ]);

        if (array_key_exists('maintenance_message', $data) && $data['maintenance_message'] !== null) {
            Settings::set('maintenance_message', $data['maintenance_message']);
        }

        if (array_key_exists('admin_igns', $data) && $data['admin_igns'] !== null) {
            Settings::setAdminIgns($data['admin_igns']);
        }

        Audit::log((int) $request->user()->id, 'settings_save', null, 'site settings', [], $request->ip());

        return back()->with('success', 'Settings saved.');
    }

    public function event(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->validate(['days' => ['required', 'integer', 'min:1', 'max:60']]);

            Settings::set('event_start', now()->toDateTimeString());
            Settings::set('event_end', now()->addDays((int) $data['days'])->toDateTimeString());

            Audit::log((int) $request->user()->id, 'event_restart', null, $data['days'].' days', [], $request->ip());

            return back()->with('success', 'DONUTTRIX event restarted for '.$data['days'].' days.');
        }

        return view('panel.event', [
            'title' => 'Event',
            'tab' => 'event',
            'status' => DonutEvent::status(),
            'board' => DonutEvent::leaderboard(10),
            'collected' => (int) DB::table('event_letters')->distinct('user_id')->count('user_id'),
            'completed' => (int) DB::table('event_rewards')->count(),
        ]);
    }

    /** Poll target for the panel's live numbers. */
    public function status(): JsonResponse
    {
        $totals = $this->wallet->totals();

        return response()->json([
            'ok' => true,
            'bot' => $this->botState(),
            'rain' => $this->rains->active(),
            'maintenance' => Settings::maintenance(),
            'totals' => $totals,
            'users' => (int) User::count(),
            'online' => (int) User::where('last_activity_at', '>=', Carbon::now()->subMinutes(10))->count(),
            'at' => now()->toIso8601String(),
        ]);
    }

    /* ------------------------------------------------------------- helpers */

    private function botState(): array
    {
        $beat = Cache::get('mc:bridge');

        return [
            'desired' => Settings::botEnabled(),
            'online' => is_array($beat)
                && ($beat['state'] ?? null) === 'online'
                && ! empty($beat['at'])
                && now()->diffInSeconds($beat['at'], true) < 90,
            'state' => $beat['state'] ?? 'offline',
            'ign' => $this->economy->botIgn(),
            'host' => $beat['host'] ?? null,
            'players' => $beat['players'] ?? null,
            'at' => $beat['at'] ?? null,
        ];
    }

    private function nextRain(): ?Carbon
    {
        $settings = Settings::rain();

        if (! $settings['enabled']) {
            return null;
        }

        $active = $this->rains->active();

        if ($active !== null) {
            return $active->ends_at !== null ? Carbon::parse($active->ends_at) : null;
        }

        $last = DB::table('rain_events')->orderByDesc('id')->first();
        $anchor = $last !== null ? Carbon::parse($last->started_at ?? $last->created_at) : null;

        return $anchor !== null ? $anchor->copy()->addMinutes($settings['interval']) : null;
    }
}
