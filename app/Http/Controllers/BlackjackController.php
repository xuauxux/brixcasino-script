<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/**
 * Blackjack against the dealer: a six-deck shoe shuffled per hand, dealt and
 * scored on the server. The browser only draws the cards it is told about, and
 * the hole card stays unknown until the hand ends.
 *
 * Hit, stand, double (first two cards only). Dealer draws to 17, blackjack pays
 * 3:2, a push returns the bet.
 */
class BlackjackController extends Controller
{
    protected $ranks = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
    protected $suits = ['spades', 'hearts', 'diamonds', 'clubs'];

    public function start(Request $r)
    {
        if ($guard = $this->guard()) return $guard;

        if (session('blackjack_round')) return $this->error('Finish the hand in play first');

        $bet = round((float) $r->bet, 2);
        if (\App\Support\Money::tooSmall($r->bet)) return $this->error(\App\Support\Money::minBetMessage());

        $user = User::where('id', Auth::id())->first();
        if ($bet > $user->balance) return $this->error('Insufficient funds');

        $deck = $this->shoe();
        $player = [$this->draw($deck), $this->draw($deck)];
        $dealer = [$this->draw($deck), $this->draw($deck)];

        $user->update(['balance' => round($user->balance - $bet, 2)]);

        app(\App\Services\Platform\Activity::class)->game($user, (float) $bet, 'blackjack');

        $round = [
            'bet' => $bet,
            'player' => $player,
            'dealer' => $dealer,
            'deck' => $deck,
            'status' => 'player',
            'doubled' => false,
        ];

        // A natural on either side settles the hand at once.
        if ($this->natural($player) || $this->natural($dealer)) {
            return $this->finish($round, $user);
        }

        session(['blackjack_round' => $round]);

        return response()->json([
            'type' => 'success',
            'round' => $this->present($round, true),
            'balance' => round($user->balance, 2),
        ]);
    }

    public function hit()
    {
        if ($guard = $this->guard()) return $guard;

        $round = session('blackjack_round');
        if (!$round || $round['status'] !== 'player') return $this->error('No hand in play');

        $round['player'][] = $this->draw($round['deck']);
        $value = $this->value($round['player']);

        if ($value >= 21) {
            $user = User::where('id', Auth::id())->first();
            return $this->finish($round, $user);
        }

        session(['blackjack_round' => $round]);

        return response()->json([
            'type' => 'success',
            'round' => $this->present($round, true),
        ]);
    }

    public function stand()
    {
        if ($guard = $this->guard()) return $guard;

        $round = session('blackjack_round');
        if (!$round || $round['status'] !== 'player') return $this->error('No hand in play');

        $user = User::where('id', Auth::id())->first();

        return $this->finish($round, $user);
    }

    public function double()
    {
        if ($guard = $this->guard()) return $guard;

        $round = session('blackjack_round');
        if (!$round || $round['status'] !== 'player') return $this->error('No hand in play');
        if (count($round['player']) !== 2 || $round['doubled']) return $this->error('You can only double on the first two cards');

        $user = User::where('id', Auth::id())->first();
        if ($round['bet'] > $user->balance) return $this->error('Not enough balance to double');

        $user->update(['balance' => round($user->balance - $round['bet'], 2)]);

        $round['bet'] = round($round['bet'] * 2, 2);
        $round['doubled'] = true;
        $round['player'][] = $this->draw($round['deck']);

        return $this->finish($round, $user);
    }

    /** Reloading the page must not lose a hand that is in play. */
    public function state()
    {
        if (Auth::guest()) return $this->error('You must sign in');

        $round = session('blackjack_round');

        if (!$round) {
            return response()->json(['type' => 'success', 'active' => false]);
        }

        return response()->json([
            'type' => 'success',
            'active' => true,
            'round' => $this->present($round, $round['status'] === 'player'),
        ]);
    }

    /* ------------------------------------------------------------------ dealing */

    protected function finish(array $round, User $user)
    {
        $playerValue = $this->value($round['player']);
        $dealerNatural = $this->natural($round['dealer']);

        // The dealer only plays when the hand is still alive and there is no natural.
        if ($playerValue <= 21 && !$this->natural($round['player'])) {
            $dealerValue = $this->value($round['dealer']);

            while ($dealerValue < 17) {
                $round['dealer'][] = $this->draw($round['deck']);
                $dealerValue = $this->value($round['dealer']);
            }
        }

        $round['status'] = 'done';

        $dealerValue = $this->value($round['dealer']);
        $bet = $round['bet'];
        $playerNatural = $this->natural($round['player']);

        if ($playerValue > 21) {
            $result = 'bust';
            $payout = 0;
        } elseif ($playerNatural && $dealerNatural) {
            $result = 'push';
            $payout = $bet;
        } elseif ($playerNatural) {
            $result = 'blackjack';
            $payout = round($bet * 2.5, 2);
        } elseif ($dealerNatural) {
            $result = 'lose';
            $payout = 0;
        } elseif ($dealerValue > 21 || $playerValue > $dealerValue) {
            $result = 'win';
            $payout = round($bet * 2, 2);
        } elseif ($playerValue === $dealerValue) {
            $result = 'push';
            $payout = $bet;
        } else {
            $result = 'lose';
            $payout = 0;
        }

        $balance = round($user->balance + $payout, 2);
        $user->update(['balance' => $balance]);

        session()->forget('blackjack_round');

        return response()->json([
            'type' => 'success',
            'round' => $this->present($round, false) + ['result' => $result, 'payout' => $payout],
            'result' => $result,
            'payout' => $payout,
            'balance' => $balance,
        ]);
    }

    protected function present(array $round, bool $hideHole)
    {
        $dealer = $round['dealer'];
        $hidden = $hideHole && count($dealer) > 1;

        $shown = $hidden
            ? [$dealer[0], ['rank' => '?', 'suit' => 'back']]
            : $dealer;

        $playerValue = $this->value($round['player']);

        return [
            'status' => $round['status'],
            'bet' => $round['bet'],
            'doubled' => $round['doubled'],
            'player' => $round['player'],
            'player_value' => $playerValue,
            'dealer' => $shown,
            'dealer_value' => $hidden ? $this->value([$dealer[0]]) : $this->value($dealer),
            'dealer_hidden' => $hidden,
            'can_hit' => $round['status'] === 'player' && $playerValue < 21,
            'can_double' => $round['status'] === 'player' && count($round['player']) === 2 && !$round['doubled'],
        ];
    }

    /** Six decks, shuffled with random_int(). */
    protected function shoe()
    {
        $deck = [];

        for ($d = 0; $d < 6; $d++) {
            foreach ($this->suits as $suit) {
                foreach ($this->ranks as $rank) {
                    $deck[] = ['rank' => $rank, 'suit' => $suit];
                }
            }
        }

        for ($i = count($deck) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            $swap = $deck[$i];
            $deck[$i] = $deck[$j];
            $deck[$j] = $swap;
        }

        return $deck;
    }

    protected function draw(array &$deck)
    {
        if (count($deck) < 2) {
            $deck = $this->shoe();
        }

        return array_pop($deck);
    }

    protected function value(array $cards)
    {
        $total = 0;
        $aces = 0;

        foreach ($cards as $card) {
            $rank = $card['rank'];

            if ($rank === '?') {
                continue;
            }

            if ($rank === 'A') {
                $total += 11;
                $aces++;
            } elseif (in_array($rank, ['K', 'Q', 'J', '10'], true)) {
                $total += 10;
            } else {
                $total += (int) $rank;
            }
        }

        while ($total > 21 && $aces > 0) {
            $total -= 10;
            $aces--;
        }

        return $total;
    }

    protected function natural(array $cards)
    {
        return count($cards) === 2 && $this->value($cards) === 21;
    }

    protected function guard()
    {
        if (Auth::guest()) {
            return $this->error('You must sign in');
        }

        return null;
    }

    protected function error($msg)
    {
        return response()->json(['type' => 'error', 'msg' => $msg]);
    }
}
