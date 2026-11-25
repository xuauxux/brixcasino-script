<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/**
 * Instant-win originals added to DonutTrix: Towers, Coin Flip, HiLo, Limbo,
 * Plinko and Keno. Every round is decided here with random_int(); the browser
 * only learns the outcome after the wallet has been settled.
 */
class OriginalsController extends Controller
{
    /** Keno pays by number of picks -> number of hits (99% RTP table). */
    protected $kenoTable = [
        1 => [0.00, 1.98],
        2 => [0.00, 0.97, 2.03],
        3 => [0.00, 0.58, 1.22, 2.57],
        4 => [0.00, 0.37, 0.78, 1.64, 3.43],
        5 => [0.00, 0.24, 0.51, 1.06, 2.23, 4.68],
        6 => [0.00, 0.16, 0.33, 0.69, 1.46, 3.06, 6.44],
        7 => [0.00, 0.10, 0.22, 0.46, 0.96, 2.02, 4.23, 8.89],
        8 => [0.00, 0.07, 0.14, 0.30, 0.63, 1.33, 2.79, 5.87, 12.33],
        9 => [0.00, 0.05, 0.10, 0.20, 0.42, 0.88, 1.85, 3.89, 8.17, 17.15],
        10 => [0.00, 0.03, 0.06, 0.13, 0.28, 0.59, 1.23, 2.59, 5.43, 11.40, 23.95],
    ];

    /** Plinko buckets, left to right. */
    protected $plinkoTable = [
        8 => [
            'low' => [8.08, 2.94, 1.47, 0.73, 0.37, 0.73, 1.47, 2.94, 8.08],
            'medium' => [16.19, 4.42, 1.47, 0.52, 0.15, 0.52, 1.47, 4.42, 16.19],
            'high' => [28.50, 5.94, 1.19, 0.24, 0.12, 0.24, 1.19, 5.94, 28.50],
        ],
        12 => [
            'low' => [10.45, 6.27, 4.18, 2.09, 1.04, 0.63, 0.42, 0.63, 1.04, 2.09, 4.18, 6.27, 10.45],
            'medium' => [14.77, 7.95, 4.54, 2.27, 1.14, 0.57, 0.23, 0.57, 1.14, 2.27, 4.54, 7.95, 14.77],
            'high' => [31.44, 15.72, 6.29, 2.62, 1.05, 0.31, 0.10, 0.31, 1.05, 2.62, 6.29, 15.72, 31.44],
        ],
        16 => [
            'low' => [20.74, 11.67, 7.78, 5.18, 2.59, 1.30, 0.91, 0.65, 0.52, 0.65, 0.91, 1.30, 2.59, 5.18, 7.78, 11.67, 20.74],
            'medium' => [34.67, 19.07, 10.40, 6.07, 3.47, 1.73, 0.87, 0.43, 0.26, 0.43, 0.87, 1.73, 3.47, 6.07, 10.40, 19.07, 34.67],
            'high' => [75.03, 37.51, 16.67, 8.34, 4.17, 1.67, 0.67, 0.33, 0.17, 0.33, 0.67, 1.67, 4.17, 8.34, 16.67, 37.51, 75.03],
        ],
    ];

    /** Towers: tiles per floor => safe tiles per floor. */
    protected $towerDifficulties = [
        'easy' => [4, 3],
        'medium' => [3, 2],
        'hard' => [2, 1],
    ];

    protected $ranks = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
    protected $suits = ['spades', 'hearts', 'diamonds', 'clubs'];

    /* ------------------------------------------------------------------ helpers */

    protected function guard()
    {
        if (Auth::guest()) {
            return response()->json(['type' => 'error', 'msg' => 'You must sign in']);
        }

        return null;
    }

    protected function bet(Request $r)
    {
        $bet = round((float) $r->bet, 2);

        if (\App\Support\Money::tooSmall($r->bet)) {
            return null;
        }

        return $bet;
    }

    protected function settle($user, $bet, $payout)
    {
        $balance = round($user->balance - $bet + $payout, 2);

        if ($balance < 0) {
            $balance = 0;
        }

        $user->update(['balance' => $balance]);

        app(\App\Services\Platform\Activity::class)->game($user, (float) $bet, 'originals');

        return $balance;
    }

    protected function towerMultiplier($options, $safe, $step)
    {
        if ($step < 1) {
            return 1.0;
        }

        return round(0.99 / pow($safe / $options, $step), 2);
    }

    protected function hiloOdds($value)
    {
        $lower = ($value - 1) * 4;
        $higher = (13 - $value) * 4;
        $equal = 3;

        if ($value === 1) {
            $higherMult = (0.99 * 51) / ($higher + $equal);
        } elseif ($value < 13) {
            $higherMult = (0.99 * (51 - $equal)) / $higher;
        } else {
            $higherMult = 0;
        }

        if ($value === 13) {
            $lowerMult = (0.99 * 51) / ($lower + $equal);
        } elseif ($value > 1) {
            $lowerMult = (0.99 * (51 - $equal)) / $lower;
        } else {
            $lowerMult = 0;
        }

        return [
            'higher' => round($higherMult, 2),
            'lower' => round($lowerMult, 2),
            'higher_chance' => (int) round(($higher / 51) * 100),
            'lower_chance' => (int) round(($lower / 51) * 100),
        ];
    }

    /* -------------------------------------------------------------- coin flip */

    public function flip(Request $r)
    {
        if ($guard = $this->guard()) return $guard;
        if (!$bet = $this->bet($r)) return response()->json(['type' => 'error', 'msg' => \App\Support\Money::minBetMessage()]);
        if (!in_array($r->side, ['heads', 'tails'], true)) return response()->json(['type' => 'error', 'msg' => 'Pick heads or tails']);

        $user = User::where('id', Auth::user()->id)->first();
        if ($bet > $user->balance) return response()->json(['type' => 'error', 'msg' => 'Insufficient funds']);

        $result = random_int(0, 1) === 0 ? 'heads' : 'tails';
        $won = $result === $r->side;
        $payout = $won ? round($bet * 1.98, 2) : 0;
        $balance = $this->settle($user, $bet, $payout);

        return response()->json([
            'type' => 'success',
            'out' => $won ? 'win' : 'lose',
            'side' => $r->side,
            'result' => $result,
            'cash' => $payout,
            'balance' => $balance,
        ]);
    }

    /* ----------------------------------------------------------------- limbo */

    public function limbo(Request $r)
    {
        if ($guard = $this->guard()) return $guard;
        if (!$bet = $this->bet($r)) return response()->json(['type' => 'error', 'msg' => \App\Support\Money::minBetMessage()]);

        $target = round((float) $r->target, 2);
        if ($target < 1.01 || $target > 1000) return response()->json(['type' => 'error', 'msg' => 'The target must be between 1.01x and 1000x']);

        $user = User::where('id', Auth::user()->id)->first();
        if ($bet > $user->balance) return response()->json(['type' => 'error', 'msg' => 'Insufficient funds']);

        $roll = random_int(0, 999999) / 1000000;
        $crash = round(0.99 / max(0.000001, 1 - $roll), 2);
        $won = $crash >= $target;
        $payout = $won ? round($bet * $target, 2) : 0;
        $balance = $this->settle($user, $bet, $payout);

        return response()->json([
            'type' => 'success',
            'out' => $won ? 'win' : 'lose',
            'crash' => $crash,
            'target' => $target,
            'cash' => $payout,
            'balance' => $balance,
        ]);
    }

    /* ----------------------------------------------------------------- plinko */

    public function plinko(Request $r)
    {
        if ($guard = $this->guard()) return $guard;
        if (!$bet = $this->bet($r)) return response()->json(['type' => 'error', 'msg' => \App\Support\Money::minBetMessage()]);

        $rows = (int) $r->rows;
        $risk = (string) $r->risk;

        if (!isset($this->plinkoTable[$rows])) return response()->json(['type' => 'error', 'msg' => 'Pick 8, 12 or 16 rows']);
        if (!in_array($risk, ['low', 'medium', 'high'], true)) return response()->json(['type' => 'error', 'msg' => 'Pick a risk level']);

        $user = User::where('id', Auth::user()->id)->first();
        if ($bet > $user->balance) return response()->json(['type' => 'error', 'msg' => 'Insufficient funds']);

        $slot = 0;
        $path = [];

        for ($i = 0; $i < $rows; $i++) {
            $right = random_int(0, 1);
            $path[] = $right;
            $slot += $right;
        }

        $mult = $this->plinkoTable[$rows][$risk][$slot];
        $payout = round($bet * $mult, 2);
        $balance = $this->settle($user, $bet, $payout);

        return response()->json([
            'type' => 'success',
            'out' => $payout > $bet ? 'win' : 'lose',
            'path' => $path,
            'slot' => $slot,
            'mult' => $mult,
            'cash' => $payout,
            'balance' => $balance,
        ]);
    }

    /* ------------------------------------------------------------------- keno */

    public function keno(Request $r)
    {
        if ($guard = $this->guard()) return $guard;
        if (!$bet = $this->bet($r)) return response()->json(['type' => 'error', 'msg' => \App\Support\Money::minBetMessage()]);

        $picks = array_values(array_unique(array_map('intval', (array) $r->picks)));
        $picks = array_values(array_filter($picks, function ($p) {
            return $p >= 1 && $p <= 40;
        }));

        if (count($picks) < 1 || count($picks) > 10) {
            return response()->json(['type' => 'error', 'msg' => 'Pick between 1 and 10 numbers']);
        }

        $user = User::where('id', Auth::user()->id)->first();
        if ($bet > $user->balance) return response()->json(['type' => 'error', 'msg' => 'Insufficient funds']);

        $pool = range(1, 40);
        shuffle($pool);
        $draw = array_slice($pool, 0, 20);
        sort($draw);

        $hits = count(array_intersect($picks, $draw));
        $mult = $this->kenoTable[count($picks)][$hits] ?? 0;
        $payout = round($bet * $mult, 2);
        $balance = $this->settle($user, $bet, $payout);

        return response()->json([
            'type' => 'success',
            'out' => $mult > 1 ? 'win' : 'lose',
            'draw' => $draw,
            'hits' => $hits,
            'mult' => $mult,
            'cash' => $payout,
            'balance' => $balance,
        ]);
    }

    /* ----------------------------------------------------------------- towers */

    public function towersState()
    {
        if ($guard = $this->guard()) return $guard;

        $round = session('towers_round');
        if (!$round) return response()->json(['type' => 'success', 'active' => false]);

        return response()->json([
            'type' => 'success',
            'active' => true,
            'step' => $round['step'],
            'floors' => $round['floors'],
            'difficulty' => $round['difficulty'],
            'mult' => $this->towerMultiplier($round['options'], $round['safe'], $round['step']),
            'next' => $this->towerMultiplier($round['options'], $round['safe'], $round['step'] + 1),
        ]);
    }

    public function towersStart(Request $r)
    {
        if ($guard = $this->guard()) return $guard;
        if (!$bet = $this->bet($r)) return response()->json(['type' => 'error', 'msg' => \App\Support\Money::minBetMessage()]);
        if (session('towers_round')) return response()->json(['type' => 'error', 'msg' => 'Finish your active tower first']);

        $difficulty = (string) $r->difficulty;
        if (!isset($this->towerDifficulties[$difficulty])) return response()->json(['type' => 'error', 'msg' => 'Pick a difficulty']);

        $user = User::where('id', Auth::user()->id)->first();
        if ($bet > $user->balance) return response()->json(['type' => 'error', 'msg' => 'Insufficient funds']);

        list($options, $safe) = $this->towerDifficulties[$difficulty];
        $floors = 8;
        $layout = [];

        for ($i = 0; $i < $floors; $i++) {
            $layout[] = random_int(0, $options - 1);
        }

        session(['towers_round' => [
            'bet' => $bet,
            'difficulty' => $difficulty,
            'options' => $options,
            'safe' => $safe,
            'floors' => $floors,
            'layout' => $layout,
            'step' => 0,
        ]]);

        $balance = round($user->balance - $bet, 2);
        $user->update(['balance' => $balance]);

        app(\App\Services\Platform\Activity::class)->game($user, (float) $bet, 'towers');

        return response()->json([
            'type' => 'success',
            'balance' => $balance,
            'floors' => $floors,
            'options' => $options,
            'difficulty' => $difficulty,
            'step' => 0,
            'mult' => 1,
            'next' => $this->towerMultiplier($options, $safe, 1),
        ]);
    }

    public function towersOpen(Request $r)
    {
        if ($guard = $this->guard()) return $guard;

        $round = session('towers_round');
        if (!$round) return response()->json(['type' => 'error', 'msg' => 'You have no active tower']);

        $pick = (int) $r->pick;
        if ($pick < 0 || $pick >= $round['options']) return response()->json(['type' => 'error', 'msg' => 'Pick a valid tile']);

        $floor = $round['step'];
        $safeTile = $round['layout'][$floor];

        if ($pick === $safeTile) {
            $round['step'] = $floor + 1;
            session(['towers_round' => $round]);

            return response()->json([
                'type' => 'success',
                'out' => 'safe',
                'pick' => $pick,
                'step' => $round['step'],
                'floors' => $round['floors'],
                'mult' => $this->towerMultiplier($round['options'], $round['safe'], $round['step']),
                'next' => $this->towerMultiplier($round['options'], $round['safe'], $round['step'] + 1),
                'complete' => $round['step'] >= $round['floors'],
            ]);
        }

        session()->forget('towers_round');
        $user = User::where('id', Auth::user()->id)->first();

        return response()->json([
            'type' => 'success',
            'out' => 'boom',
            'pick' => $pick,
            'safe' => $safeTile,
            'layout' => $round['layout'],
            'balance' => $user->balance,
        ]);
    }

    public function towersTake()
    {
        if ($guard = $this->guard()) return $guard;

        $round = session('towers_round');
        if (!$round) return response()->json(['type' => 'error', 'msg' => 'You have no active tower']);
        if ($round['step'] < 1) return response()->json(['type' => 'error', 'msg' => 'Climb at least one floor first']);

        $mult = $this->towerMultiplier($round['options'], $round['safe'], $round['step']);
        $payout = round($round['bet'] * $mult, 2);

        $user = User::where('id', Auth::user()->id)->first();
        $balance = round($user->balance + $payout, 2);
        $user->update(['balance' => $balance]);

        session()->forget('towers_round');

        return response()->json([
            'type' => 'success',
            'out' => 'take',
            'cash' => $payout,
            'mult' => $mult,
            'balance' => $balance,
        ]);
    }

    /* ------------------------------------------------------------------- hilo */

    protected function hiloCard($value, $suit)
    {
        return ['rank' => $this->ranks[$value - 1], 'suit' => $this->suits[$suit], 'value' => $value];
    }

    protected function hiloState($round)
    {
        $odds = $this->hiloOdds($round['card']['value']);

        return [
            'type' => 'success',
            'active' => true,
            'card' => $round['card'],
            'mult' => round($round['mult'], 2),
            'cash' => round($round['bet'] * $round['mult'], 2),
            'odds' => $odds,
        ];
    }

    public function hiloStart(Request $r)
    {
        if ($guard = $this->guard()) return $guard;
        if (!$bet = $this->bet($r)) return response()->json(['type' => 'error', 'msg' => \App\Support\Money::minBetMessage()]);
        if (session('hilo_round')) return response()->json(['type' => 'error', 'msg' => 'Finish your active round first']);

        $user = User::where('id', Auth::user()->id)->first();
        if ($bet > $user->balance) return response()->json(['type' => 'error', 'msg' => 'Insufficient funds']);

        $round = [
            'bet' => $bet,
            'mult' => 1.0,
            'card' => $this->hiloCard(random_int(1, 13), random_int(0, 3)),
        ];

        session(['hilo_round' => $round]);
        $user->update(['balance' => round($user->balance - $bet, 2)]);

        app(\App\Services\Platform\Activity::class)->game($user, (float) $bet, 'hilo');

        $state = $this->hiloState($round);
        $state['balance'] = round($user->balance - $bet, 2);

        return response()->json($state);
    }

    public function hiloGuess(Request $r)
    {
        if ($guard = $this->guard()) return $guard;

        $round = session('hilo_round');
        if (!$round) return response()->json(['type' => 'error', 'msg' => 'Start a round first']);

        $guess = (string) $r->guess;
        if (!in_array($guess, ['higher', 'lower'], true)) return response()->json(['type' => 'error', 'msg' => 'Guess higher or lower']);

        $odds = $this->hiloOdds($round['card']['value']);
        $multiplier = $guess === 'higher' ? $odds['higher'] : $odds['lower'];

        if ($multiplier <= 0) return response()->json(['type' => 'error', 'msg' => 'That guess is not possible on this card']);

        do {
            $value = random_int(1, 13);
            $suit = random_int(0, 3);
        } while ($value === $round['card']['value'] && $this->suits[$suit] === $round['card']['suit']);

        $next = $this->hiloCard($value, $suit);
        $previous = $round['card']['value'];

        if ($value === $previous) {
            $round['card'] = $next;
            session(['hilo_round' => $round]);
            $state = $this->hiloState($round);
            $state['out'] = 'push';

            return response()->json($state);
        }

        $won = $guess === 'higher' ? $value > $previous : $value < $previous;

        if ($won) {
            $round['mult'] = round($round['mult'] * $multiplier, 4);
            $round['card'] = $next;
            session(['hilo_round' => $round]);
            $state = $this->hiloState($round);
            $state['out'] = 'win';

            return response()->json($state);
        }

        session()->forget('hilo_round');
        $user = User::where('id', Auth::user()->id)->first();

        return response()->json([
            'type' => 'success',
            'out' => 'lose',
            'card' => $next,
            'balance' => $user->balance,
        ]);
    }

    public function hiloTake()
    {
        if ($guard = $this->guard()) return $guard;

        $round = session('hilo_round');
        if (!$round) return response()->json(['type' => 'error', 'msg' => 'You have no active round']);

        $payout = round($round['bet'] * $round['mult'], 2);
        $user = User::where('id', Auth::user()->id)->first();
        $balance = round($user->balance + $payout, 2);
        $user->update(['balance' => $balance]);

        session()->forget('hilo_round');

        return response()->json([
            'type' => 'success',
            'out' => 'take',
            'cash' => $payout,
            'mult' => round($round['mult'], 2),
            'balance' => $balance,
        ]);
    }
}
