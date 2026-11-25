@extends('layout')

@section('content')
<div class="towers__container">
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
                <label class="games__sidebar_label">Difficulty</label>
                <div class="games__sidebar_help_bombs">
                    <button type="button" class="games__sidebar_bombs_action tower__diff active" data-tower-difficulty="easy">Easy</button>
                    <button type="button" class="games__sidebar_bombs_action tower__diff" data-tower-difficulty="medium">Medium</button>
                    <button type="button" class="games__sidebar_bombs_action tower__diff" data-tower-difficulty="hard">Hard</button>
                </div>
            </div>
            <div class="games__stats">
                <div class="games__stat">
                    <span class="games__stat_label">Multiplier</span>
                    <span class="games__stat_value" id="towers_mult">1.00x</span>
                </div>
                <div class="games__stat">
                    <span class="games__stat_label">Cash out</span>
                    <span class="games__stat_value" id="towers_cash">0.00</span>
                </div>
            </div>
            <button type="button" class="sidebar__play" id="towers_play">Start climbing</button>
            <button type="button" class="sidebar__take_win" id="towers_take" style="display:none">Cash out</button>
            <p class="games__result" id="towers_hint"></p>
        </div>
        <div class="games__field towers__field">
            <div class="towers__board" id="towers_board"></div>
        </div>
    </div>
    <script src="/assets/js/originals.js"></script>
</div>

@include('partials.other-games', ['current' => 'towers'])

@endsection
