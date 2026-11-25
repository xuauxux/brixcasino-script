<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Platform\Activity;
use App\Services\Platform\Catalogue;
use App\Services\Platform\DonutEvent;
use App\Services\Platform\Leaderboards;
use App\Services\Platform\Levels;
use App\Services\Platform\Rains;
use App\Services\Platform\Referrals;
use App\Services\Platform\Settings;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Player-facing pages: dashboard, games, event, competitions and referrals. */
class PlayerController extends Controller
{
    public function __construct(
        private readonly Rains $rains,
        private readonly Activity $activity,
    ) {}

    public function dashboard()
    {
        $user = Auth::user();

        if ($user === null) {
            return redirect('/')->with('error', 'Please sign in.');
        }

        Levels::sync($user);
        $this->rains->tick();

        $progress = Levels::progress($user);

        return view('dashboard', [
            'user' => $user,
            'progress' => $progress,
            'levelRewards' => Levels::REWARDS,
            'upcoming' => Levels::upcoming($progress['level']),
            'referrals' => Referrals::stats($user),
            'boards' => [
                'daily' => Leaderboards::top('daily', 5),
                'weekly' => Leaderboards::top('weekly', 5),
                'monthly' => Leaderboards::top('monthly', 5),
            ],
            'positions' => [
                'daily' => Leaderboards::position($user, 'daily'),
                'weekly' => Leaderboards::position($user, 'weekly'),
                'monthly' => Leaderboards::position($user, 'monthly'),
            ],
            'endsAt' => [
                'daily' => Leaderboards::endsAt('daily'),
                'weekly' => Leaderboards::endsAt('weekly'),
                'monthly' => Leaderboards::endsAt('monthly'),
            ],
            'rain' => [
                'active' => $this->rains->active(),
                'settings' => Settings::rain(),
            ],
            'games' => Catalogue::all(),
            'activity' => $this->activity->recent((int) $user->id, 6),
            'announcements' => $this->announcements(),
        ]);
    }

    public function games()
    {
        return view('games', [
            'games' => Catalogue::all(),
        ]);
    }

    public function leaderboards()
    {
        $user = Auth::user();

        return view('leaderboards', [
            'boards' => [
                'daily' => Leaderboards::top('daily', 10),
                'weekly' => Leaderboards::top('weekly', 10),
                'monthly' => Leaderboards::top('monthly', 10),
            ],
            'referralBoard' => Referrals::top(10),
            'endsAt' => [
                'daily' => Leaderboards::endsAt('daily'),
                'weekly' => Leaderboards::endsAt('weekly'),
                'monthly' => Leaderboards::endsAt('monthly'),
            ],
            'positions' => $user !== null ? [
                'daily' => Leaderboards::position($user, 'daily'),
                'weekly' => Leaderboards::position($user, 'weekly'),
                'monthly' => Leaderboards::position($user, 'monthly'),
            ] : [],
        ]);
    }

    public function event()
    {
        $this->rains->tick();

        $rain = $this->rains->active();
        $user = Auth::user();

        $joined = $rain !== null && $user !== null
            && DB::table('rain_participants')
                ->where('rain_id', $rain->id)
                ->where('user_id', $user->id)
                ->exists();

        return view('event', [
            'rain' => $rain,
            'settings' => Settings::rain(),
            'joined' => $joined,
            'history' => $this->rains->history(6),
            'seconds' => $rain !== null && $rain->ends_at !== null
                ? max(0, Carbon::now()->diffInSeconds(Carbon::parse($rain->ends_at), false))
                : 0,
        ]);
    }

    /** A free letter every six hours, so the event is playable without paying. */
    public function freeLetter(Request $request)
    {
        $user = Auth::user();

        if ($user === null) {
            return redirect('/')->with('error', 'Please sign in.');
        }

        if (! DonutEvent::status()['active']) {
            return back()->with('error', 'The DONUTTRIX event has ended.');
        }

        $last = \DB::table('activity_events')
            ->where('user_id', $user->id)
            ->where('source', 'event_free')
            ->orderByDesc('id')
            ->first();

        if ($last !== null && Carbon::parse($last->created_at)->addHours(6)->isFuture()) {
            $wait = Carbon::parse($last->created_at)->addHours(6);

            return back()->with('error', 'Your next free letter is available '.$wait->diffForHumans().'.');
        }

        $progress = DonutEvent::progress($user);

        if ($progress['missing'] === []) {
            return back()->with('error', 'You already collected every letter.');
        }

        \DB::table('activity_events')->insert([
            'user_id' => $user->id,
            'points' => 50,
            'xp' => 25,
            'source' => 'event_free',
            'created_at' => now(),
        ]);

        $letter = (new DonutEvent())->grant($user, 'free');

        return back()->with($letter ? 'success' : 'error', $letter
            ? 'You collected the letter '.$letter.'.'
            : 'No letter this time — try again later.');
    }

    public function rainJoin()
    {
        $user = Auth::user();

        if ($user === null) {
            return redirect('/')->with('error', 'Please sign in.');
        }

        $result = $this->rains->join($user);

        return back()->with($result['ok'] ? 'success' : 'error', $result['ok']
            ? 'You joined the rain. The pot is split when the countdown ends.'
            : ($result['error'] ?? 'Could not join.'));
    }

    public function rainStatus(): JsonResponse
    {
        $rain = $this->rains->active();

        return response()->json([
            'ok' => true,
            'active' => $rain !== null,
            'amount' => $rain->amount ?? null,
            'participants' => $rain->participants ?? 0,
            'ends_at' => $rain->ends_at ?? null,
            'seconds_left' => $rain !== null && $rain->ends_at !== null
                ? max(0, Carbon::now()->diffInSeconds(Carbon::parse($rain->ends_at), false))
                : 0,
        ]);
    }

    private function announcements(): array
    {
        $raw = Settings::get('announcements');

        if ($raw === null) {
            return [
                ['title' => 'DONUTTRIX event is live', 'body' => 'Collect all nine letters to win 100,000,000 points.'],
                ['title' => 'New competitions every day', 'body' => 'Daily, weekly and monthly leaderboards pay 1st–3rd automatically.'],
            ];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
