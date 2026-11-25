@extends('layout')

@section('content')
<div class="limbo__container">
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
                <label class="games__sidebar_label">Target multiplier</label>
                <div class="games__sidebar_wrapper_input">
                    <input type="number" class="games__sidebar_input input__target" min="1.01" max="1000" step="0.01" value="2.00">
                </div>
                <div class="games__sidebar_help_bombs">
                    <button type="button" class="games__sidebar_bombs_action" data-target="1.5">1.5x</button>
                    <button type="button" class="games__sidebar_bombs_action" data-target="2">2x</button>
                    <button type="button" class="games__sidebar_bombs_action" data-target="5">5x</button>
                    <button type="button" class="games__sidebar_bombs_action" data-target="10">10x</button>
                    <button type="button" class="games__sidebar_bombs_action" data-target="100">100x</button>
                </div>
            </div>
            <div class="games__stats">
                <div class="games__stat">
                    <span class="games__stat_label">Win chance</span>
                    <span class="games__stat_value" id="limbo_chance">49.50%</span>
                </div>
                <div class="games__stat">
                    <span class="games__stat_label">Payout</span>
                    <span class="games__stat_value" id="limbo_payout">0.00</span>
                </div>
            </div>
            <button type="button" class="sidebar__play" id="limbo_play">Play</button>
        </div>
        <div class="games__field limbo__field">
            <div class="limbo__stage">
                <div class="limbo__value" id="limbo_value">1.00x</div>
                <div class="limbo__result" id="limbo_result">Set a target and roll</div>
            </div>
        </div>
    </div>
    <script src="/assets/js/originals.js"></script>
</div>

@include('partials.other-games', ['current' => 'limbo'])

@endsection
