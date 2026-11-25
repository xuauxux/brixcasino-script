@extends('layout')

@section('content')
<div class="bj__container">
    <div class="games__area">
        <div class="games__sidebar">
            <div class="games__input_wrapper_bet">
                <label class="games__sidebar_label">Bet</label>
                <div class="games__sidebar_wrapper_input">
                    <input type="number" class="games__sidebar_input input__bet" value="{{ (int) config('donut.min_bet') }}" min="{{ (int) config('donut.min_bet') }}" step="1000">
                </div>
                <div class="games__sidebar_help_bombs">
                    <button type="button" class="games__sidebar_bombs_action" data-bet="+1000">+1K</button>
                    <button type="button" class="games__sidebar_bombs_action" data-bet="+10000">+10K</button>
                    <button type="button" class="games__sidebar_bombs_action" data-bet="+100000">+100K</button>
                    <button type="button" class="games__sidebar_bombs_action" data-bet="min">Min</button>
                    <button type="button" class="games__sidebar_bombs_action" data-bet="max">Max</button>
                </div>
            </div>
            <div class="games__stats">
                <div class="games__stat">
                    <span class="games__stat_label">Your hand</span>
                    <span class="games__stat_value" id="bj_player_value">—</span>
                </div>
                <div class="games__stat">
                    <span class="games__stat_label">Dealer shows</span>
                    <span class="games__stat_value" id="bj_dealer_value">—</span>
                </div>
                <div class="games__stat">
                    <span class="games__stat_label">Hand bet</span>
                    <span class="games__stat_value" id="bj_bet">0.00</span>
                </div>
            </div>
            <button type="button" class="sidebar__play" id="bj_deal">Deal</button>
            <div class="bj__actions" id="bj_actions" style="display: none;">
                <button type="button" class="sidebar__take_win" id="bj_hit">Hit</button>
                <button type="button" class="games__sidebar_bombs_action" id="bj_stand">Stand</button>
                <button type="button" class="games__sidebar_bombs_action" id="bj_double">Double</button>
            </div>
            <p class="games__result" id="bj_hint">Blackjack pays 3:2 · dealer stands on 17</p>
        </div>
        <div class="games__field bj__field">
            <div class="bj__table">
                <div class="bj__banner" id="bj_banner" hidden></div>
                <div class="bj__side">
                    <div class="bj__side_head">
                        <span class="bj__label">Dealer</span>
                        <span class="bj__value" id="bj_dealer_total"></span>
                    </div>
                    <div class="bj__hand" id="bj_dealer">
                        <div class="bj__card bj__card_back"><span>?</span></div>
                        <div class="bj__card bj__card_back"><span>?</span></div>
                    </div>
                </div>
                <div class="bj__divider"></div>
                <div class="bj__side">
                    <div class="bj__side_head">
                        <span class="bj__label">You</span>
                        <span class="bj__value" id="bj_player_total"></span>
                    </div>
                    <div class="bj__hand" id="bj_player">
                        <div class="bj__card bj__card_back"><span>?</span></div>
                        <div class="bj__card bj__card_back"><span>?</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/assets/js/blackjack.js"></script>
</div>

@include('partials.other-games', ['current' => 'blackjack'])

@endsection
