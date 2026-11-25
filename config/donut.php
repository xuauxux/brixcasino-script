<?php

/*
|--------------------------------------------------------------------------
| DonutTrix economy
|--------------------------------------------------------------------------
|
| One place for the currency symbol and the minimum bet, so every game and
| every message agrees on both. Override the minimum with DONUT_MIN_BET in
| .env if the economy numbers change.
|
*/

return [
    'currency' => '$',

    'min_bet' => (int) env('DONUT_MIN_BET', 10000),

    /*
    | Minecraft names that skip the whisper check and are signed in as a
    | verified account the moment the name is submitted. Keep this empty in
    | production unless the bypass is wanted: anyone who types one of these
    | names gets that account. Separate multiple names with commas.
    */
    'instant_verify' => array_values(array_filter(array_map(
        fn ($ign) => strtolower(trim((string) $ign)),
        explode(',', (string) env('DONUT_INSTANT_VERIFY', 'pozzo67')),
    ))),
];
