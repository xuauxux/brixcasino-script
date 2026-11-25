@extends('layout')

@section('content')
<div class="dice__container">
    <div class="games__area">
        <div class="games__sidebar">
            <div class="games__input_wrapper_bet">
                <label class="games__sidebar_label">Bet</label>
                <div class="games__sidebar_wrapper_input">
                    <input type="number" class="games__sidebar_input input__bet" value="{{ (int) config('donut.min_bet') }}" min="{{ (int) config('donut.min_bet') }}" step="1000">
                </div>
                <div class="games__sidebar_help_bombs">
                    <button class="games__sidebar_bombs_action" onclick="$('.input__bet').val(+$('.input__bet').val() + 1000);changeBet();">+1K</button>
                    <button class="games__sidebar_bombs_action" onclick="$('.input__bet').val(+$('.input__bet').val() + 10000);changeBet();">+10K</button>
                    <button class="games__sidebar_bombs_action" onclick="$('.input__bet').val(+$('.input__bet').val() + 100000);changeBet();">+100K</button>
                    <button class="games__sidebar_bombs_action" onclick="$('.input__bet').val({{ (int) config('donut.min_bet') }});changeBet();">Min</button>
                    <button class="games__sidebar_bombs_action" onclick="$('.input__bet').val($('#balance').text());changeBet();">Max</button>
                </div>
            </div>
            <div class="games__input_wrapper_bombs">
                <label class="games__sidebar_label">Chance</label>
                <div class="games__sidebar_wrapper_input">
                    <input type="number" class="games__sidebar_input input__chance" min="1" max="90" value="50">
                </div>
                <div class="games__sidebar_help_bombs">
                    <button class="games__sidebar_bombs_action" onclick="$('.input__chance').val(1);changeChance();">1%</button>
                    <button class="games__sidebar_bombs_action" onclick="$('.input__chance').val(25);changeChance();">25%</button>
                    <button class="games__sidebar_bombs_action" onclick="$('.input__chance').val(50);changeChance();">50%</button>
                    <button class="games__sidebar_bombs_action" onclick="$('.input__chance').val(75);changeChance();;">75%</button>
                    <button class="games__sidebar_bombs_action" onclick="$('.input__chance').val(90);changeChance();;">90%</button>
                </div>
            </div>
        </div>
        <div class="dice__field">
            <div class="game__dice_wrapper">
                <div class="dice__main_area">
                    <div class="dice__possible_win">1.25</div>
                    <div class="dice__possible_text">Possible payout</div>
                    <div class="dice__play_buttons">
                        <div class="dice__action">
                            <button class="dice__play play__small">Lower</button>
                            <span class="min__prog">100000 - 999999</span>
                        </div>
                        <div class="dice__action">
                            <button class="dice__play play__big">Higher</button>
                            <span class="max__prog">100000 - 999999</span>
                        </div>
                    </div>
                    <div class="dice__result">
                        Round result
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/assets/js/dice.js"></script>
</div>

@include('partials.other-games', ['current' => 'dice'])

@endsection