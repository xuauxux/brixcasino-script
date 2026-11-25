@extends('layout')

@section('content')
<div class="plinko__container">
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
                <label class="games__sidebar_label">Risk</label>
                <div class="games__sidebar_help_bombs">
                    <button type="button" class="games__sidebar_bombs_action plinko__risk" data-plinko-risk="low">Low</button>
                    <button type="button" class="games__sidebar_bombs_action plinko__risk active" data-plinko-risk="medium">Medium</button>
                    <button type="button" class="games__sidebar_bombs_action plinko__risk" data-plinko-risk="high">High</button>
                </div>
            </div>
            <div class="games__input_wrapper_bombs">
                <label class="games__sidebar_label">Rows</label>
                <div class="games__sidebar_help_bombs">
                    <button type="button" class="games__sidebar_bombs_action plinko__rows" data-plinko-rows="8">8</button>
                    <button type="button" class="games__sidebar_bombs_action plinko__rows active" data-plinko-rows="12">12</button>
                    <button type="button" class="games__sidebar_bombs_action plinko__rows" data-plinko-rows="16">16</button>
                </div>
            </div>
            <button type="button" class="sidebar__play" id="plinko_play">Drop ball</button>
        </div>
        <div class="games__field plinko__field">
            <canvas class="plinko__canvas" id="plinko_canvas" width="720" height="560"></canvas>
        </div>
    </div>
    <script src="/assets/js/originals.js"></script>
</div>

@include('partials.other-games', ['current' => 'plinko'])

@endsection
