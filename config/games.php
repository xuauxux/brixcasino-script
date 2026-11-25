<?php

/*
| Catalogue of playable games. `setting` is the column in the existing
| `settings` row where 1 means "temporarily disabled" (the legacy convention),
| `null` means the game has no switch and is always on.
*/

return [

    'dice' => [
        'name' => 'Dice',
        'tag' => 'Originals',
        'description' => 'Roll under or over a number and pick your own win chance.',
        'setting' => 'dice_enabled',
        'image' => 'dice.png',
    ],
    'mines' => [
        'name' => 'Mines',
        'tag' => 'Originals',
        'description' => 'Reveal safe tiles, avoid the bombs, cash out whenever you like.',
        'setting' => 'mines_enabled',
        'image' => 'mines.png',
    ],
    'wheel' => [
        'name' => 'Wheel',
        'tag' => 'Originals',
        'description' => 'Pick a colour, spin the wheel, land a multiplier.',
        'setting' => 'wheel_enabled',
        'image' => 'wheel.png',
    ],
    'jackpot' => [
        'name' => 'Jackpot',
        'tag' => 'Jackpot',
        'description' => 'Everyone pays in, one ticket takes the whole pot.',
        'setting' => 'jackpot_enabled',
        'image' => 'jackpot.png',
    ],
    'crash' => [
        'name' => 'Crash',
        'tag' => 'Originals',
        'description' => 'Ride the multiplier and cash out before it crashes.',
        'setting' => 'crash_enabled',
        'image' => 'crash.png',
    ],
    'towers' => [
        'name' => 'Towers',
        'tag' => 'Originals',
        'description' => 'Climb floor by floor, each step multiplies the payout.',
        'setting' => null,
        'image' => 'towers.png',
    ],
    'flip' => [
        'name' => 'Coin Flip',
        'tag' => 'Originals',
        'description' => 'Heads or tails, settled in one flip.',
        'setting' => null,
        'image' => 'flip.png',
    ],
    'hilo' => [
        'name' => 'HiLo',
        'tag' => 'Cards',
        'description' => 'Guess whether the next card is higher or lower.',
        'setting' => null,
        'image' => 'hilo.png',
    ],
    'limbo' => [
        'name' => 'Limbo',
        'tag' => 'Originals',
        'description' => 'Pick a target multiplier and roll the dice on it.',
        'setting' => null,
        'image' => 'limbo.png',
    ],
    'plinko' => [
        'name' => 'Plinko',
        'tag' => 'Originals',
        'description' => 'Drop the ball and let the pegs decide the multiplier.',
        'setting' => null,
        'image' => 'plinko.png',
    ],
    'keno' => [
        'name' => 'Keno',
        'tag' => 'Originals',
        'description' => 'Pick your numbers and match the draw.',
        'setting' => null,
        'image' => 'keno.png',
    ],
    'blackjack' => [
        'name' => 'Blackjack',
        'tag' => 'Table',
        'description' => 'Beat the dealer to 21 without going bust.',
        'setting' => null,
        'image' => 'blackjack.png',
    ],

];
