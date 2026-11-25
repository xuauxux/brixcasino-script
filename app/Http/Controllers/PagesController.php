<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Datatables;
use App\Models\Withdraws;
use App\Models\Payments;
use App\Models\Settings;
use App\Models\Wheel;
use App\Models\Slots;
use App\Models\WheelBets;

class PagesController extends Controller
{
    public function __construct(Request $r)
    {
        $this->settings = Settings::where('id', 1)->first();
    }
    public function index() {
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);

        $user = Auth::user();
        $games = \App\Services\Platform\Catalogue::all();
        $enabledGames = array_filter($games, fn ($game) => $game['enabled']);

        return view('index', [
            'enabledGames' => $enabledGames,
            'rain' => [
                'active' => app(\App\Services\Platform\Rains::class)->active(),
                'settings' => \App\Services\Platform\Settings::rain(),
            ],
            'event' => \App\Services\Platform\DonutEvent::status(),
            'progress' => $user !== null ? \App\Services\Platform\DonutEvent::progress($user) : null,
            'level' => $user !== null ? \App\Services\Platform\Levels::progress($user) : null,
            'position' => $user !== null ? \App\Services\Platform\Leaderboards::position($user, 'daily') : null,
            'online' => (int) \App\Models\User::where('last_activity_at', '>=', now()->subMinutes(10))->count(),
        ]);
    }

    public function slots() {
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        return view('slots');
    }   

    public function bonus() {
        if(!Auth::user()) return redirect('/')->with('error', 'Please sign in');
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        return view('bonus');
    }

    public function mines() {
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        if($this->settings->mines_enabled == 1) return redirect('/')->with('error', 'This game mode is currently unavailable!');
        return view('mines');
    }

    public function dice() {
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        if($this->settings->dice_enabled == 1) return redirect('/')->with('error', 'This game mode is currently unavailable!');
        return view('dice');
    }

    public function towers() {
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        return view('towers');
    }

    public function flip() {
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        return view('flip');
    }

    public function hilo() {
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        return view('hilo');
    }

    public function limbo() {
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        return view('limbo');
    }

    public function plinko() {
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        return view('plinko');
    }

    public function keno() {
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        return view('keno');
    }

    public function blackjack() {
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        return view('blackjack');
    }

    public function deposit() {
        if(!Auth::user()) return redirect('/')->with('error', 'Please sign in');
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        return view('deposit');
    }

    public function withdraw() {
        if(!Auth::user()) return redirect('/')->with('error', 'Please sign in');
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        return view('withdraw');
    }

    public function history(Request $r) {
        if(!Auth::user()) return redirect('/')->with('error', 'Please sign in');
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        $history_pay = Payments::where('user_id', Auth::user()->id)->orderBy('id', 'desc')->paginate(10); 
        $history_with = Withdraws::where('user_id', Auth::user()->id)->orderBy('id', 'desc')->paginate(10); 
        return view('history', compact('history_pay', 'history_with'));
    }

    public function referrals() {
        if(!Auth::user()) return redirect('/')->with('error', 'Please sign in');
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        $settings = $this->settings;
        $stats = \App\Services\Platform\Referrals::stats(Auth::user());
        $board = \App\Services\Platform\Referrals::top(10);
        $historyRows = \App\Models\User::where('referred_by', Auth::id())
            ->orderByDesc('id')
            ->limit(12)
            ->get(['username', 'created_at']);

        return view('referrals', compact('settings', 'stats', 'board', 'historyRows'));
    }

    public function wheel() {
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        $bets = $this->getBets();
        $bank = [$this->getBankForGame('black'), $this->getBankForGame('yellow'), $this->getBankForGame('red'), $this->getBankForGame('green')];
        $history = $this->getHistory();
        if($this->settings->wheel_enabled == 1) return redirect('/')->with('error', 'This game mode is currently unavailable!');
        return view('wheel', compact('bets', 'bank', 'history'));
    }

    public function rules() {
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        return view('rules');
    }

    public function privacy() {
        if($this->settings->tech_work == 1) return response()->view('errors.techworks', [], 404);
        return view('privacy');
    }

    private function getBets() {
        $game = Wheel::orderBy('id', 'desc')->first();
        $bets = WheelBets::where('wheel_bets.game_id', $game->id)
            ->select('wheel_bets.user_id', DB::raw('SUM(wheel_bets.price) as sum'), 'users.username', 'users.avatar', 'wheel_bets.color', 'wheel_bets.balance')
            ->join('users', 'users.id', '=', 'wheel_bets.user_id')
            ->groupBy('wheel_bets.user_id', 'wheel_bets.color', 'wheel_bets.balance')
            ->orderBy('sum', 'desc')
            ->get();
        return $bets;
    }

    public function getHistory() {
        $query = Wheel::where('status', 2)->select('winner_color', 'id')->orderBy('id','desc')->limit(112)->get();
        return $query;
    }

    public function getBankForGame($color) {
        $game = Wheel::orderBy('id', 'desc')->first();
        $bank = WheelBets::where('game_id', $game->id)->where('color', $color)->sum('price');
        return number_format(round($bank, 2), 2, '.', '');
    }
}
