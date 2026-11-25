@extends('layout')

@section('content')
<div class="flip__container">
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
            <div class="games__input_wrapper_bombs">
                <label class="games__sidebar_label">Pick a side</label>
                <div class="games__sidebar_help_bombs">
                    <button type="button" class="games__sidebar_bombs_action flip__side active" data-flip-side="heads">Heads</button>
                    <button type="button" class="games__sidebar_bombs_action flip__side" data-flip-side="tails">Tails</button>
                </div>
            </div>
            <div class="games__stats">
                <div class="games__stat">
                    <span class="games__stat_label">Payout</span>
                    <span class="games__stat_value">1.98x</span>
                </div>
                <div class="games__stat">
                    <span class="games__stat_label">Win chance</span>
                    <span class="games__stat_value">49.50%</span>
                </div>
            </div>
            <button type="button" class="sidebar__play" id="flip_play">Flip coin</button>
        </div>
        <div class="games__field flip__field">
            <div class="flip__stage">
                <div class="flip__coin" id="flip_coin">
                    <span class="flip__face flip__face_heads">H</span>
                    <span class="flip__face flip__face_tails">T</span>
                </div>
                <div class="flip__result" id="flip_result">Pick heads or tails and flip</div>
            </div>
        </div>
    </div>
    <script src="/assets/js/originals.js"></script>
</div>

@include('partials.other-games', ['current' => 'flip'])

@endsection
