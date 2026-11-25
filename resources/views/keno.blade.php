@extends('layout')

@section('content')
<div class="keno__container">
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
                    <span class="games__stat_label">Picked</span>
                    <span class="games__stat_value" id="keno_picked">0 / 10</span>
                </div>
                <div class="games__stat">
                    <span class="games__stat_label">Hits</span>
                    <span class="games__stat_value" id="keno_hits">0</span>
                </div>
            </div>
            <button type="button" class="sidebar__play" id="keno_play">Play</button>
            <button type="button" class="games__sidebar_bombs_action keno__clear" id="keno_clear">Clear picks</button>
        </div>
        <div class="games__field keno__field">
            <div class="keno__grid" id="keno_grid"></div>
        </div>
    </div>
    <script src="/assets/js/originals.js"></script>
</div>

@include('partials.other-games', ['current' => 'keno'])

@endsection
