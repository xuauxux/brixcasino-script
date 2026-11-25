@extends('layout')

@section('content')
<div class="hilo__container">
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
                    <span class="games__stat_label">Multiplier</span>
                    <span class="games__stat_value" id="hilo_mult">1.00x</span>
                </div>
                <div class="games__stat">
                    <span class="games__stat_label">Cash out</span>
                    <span class="games__stat_value" id="hilo_cash">0.00</span>
                </div>
            </div>
            <button type="button" class="sidebar__play" id="hilo_start">Deal a card</button>
            <div class="hilo__actions" id="hilo_actions" style="display:none">
                <button type="button" class="hilo__guess hilo__higher" id="hilo_higher">Higher <b id="hilo_higher_mult">0.00x</b></button>
                <button type="button" class="hilo__guess hilo__lower" id="hilo_lower">Lower <b id="hilo_lower_mult">0.00x</b></button>
            </div>
            <button type="button" class="sidebar__take_win" id="hilo_take" style="display:none">Cash out</button>
        </div>
        <div class="games__field hilo__field">
            <div class="hilo__stage">
                <div class="hilo__card" id="hilo_card">
                    <span class="hilo__rank" id="hilo_rank">?</span>
                    <span class="hilo__suit" id="hilo_suit">&#9830;</span>
                </div>
                <div class="hilo__result" id="hilo_result">Deal a card to start</div>
            </div>
        </div>
    </div>
    <script src="/assets/js/originals.js"></script>
</div>

@include('partials.other-games', ['current' => 'hilo'])

@endsection
