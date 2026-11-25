<?php

/*
| DonutSMP in-game economy.
|
| Players pay the bridge bot in game; the bot reports the receipt and the site
| credits the wallet. The same bot reports the 8-character code a player
| whispers in game, which is what verifies their IGN at sign-up and login.
|
| Rates: game_amount is whole in-game dollars, coins are site currency.
*/

return [

    // Shared with minecraft-bridge/.env — every bot callback is HMAC signed
    // with this value, so keep it long and private.
    'secret' => env('MC_BRIDGE_SECRET', ''),

    // Leave empty to learn the account from the bot's own login name.
    'bot_ign' => env('MC_BOT_IGN', ''),

    // Site currency granted per 1,000,000 in-game dollars.
    'coins_per_million' => (int) env('MC_COINS_PER_MILLION', 100),

    // How long a whispered verification code stays valid.
    'link_ttl_minutes' => (int) env('MC_LINK_TTL_MINUTES', 10),

    'deposit' => [
        'enabled' => filter_var(env('MC_DEPOSIT_ENABLED', true), FILTER_VALIDATE_BOOL),
        'enabled_min' => true,
        'min_game' => (int) env('MC_DEPOSIT_MIN_GAME', 1_000_000),
        'max_game' => (int) env('MC_DEPOSIT_MAX_GAME', 100_000_000_000),
        // A pending request the bot never sees expires after this long.
        'request_ttl_minutes' => (int) env('MC_DEPOSIT_TTL_MINUTES', 15),
        'max_pending' => (int) env('MC_DEPOSIT_MAX_PENDING', 3),
        // Deposit buttons, in in-game money.
        'tiers' => [
            ['id' => 'starter', 'name' => 'Starter', 'game' => 10_000_000],
            ['id' => 'basic', 'name' => 'Basic', 'game' => 100_000_000],
            ['id' => 'popular', 'name' => 'Popular', 'game' => 500_000_000],
            ['id' => 'high-roller', 'name' => 'High Roller', 'game' => 1_000_000_000],
            ['id' => 'whale', 'name' => 'Whale', 'game' => 2_000_000_000],
            ['id' => 'addict', 'name' => 'Addict', 'game' => 5_000_000_000],
        ],
    ],

    'withdraw' => [
        'enabled' => filter_var(env('MC_WITHDRAW_ENABLED', true), FILTER_VALIDATE_BOOL),
        'min_game' => (int) env('MC_WITHDRAW_MIN_GAME', 1_000_000),
        'max_game' => (int) env('MC_WITHDRAW_MAX_GAME', 5_000_000_000),
        'daily_game' => (int) env('MC_WITHDRAW_DAILY_GAME', 20_000_000_000),
        // DonutSMP charges this on top of every /pay. The house covers it, but
        // it still comes out of the bot's float.
        'tax_bps' => (int) env('MC_WITHDRAW_TAX_BPS', 200),
        'max_pending' => (int) env('MC_WITHDRAW_MAX_PENDING', 1),
        'pending_ttl_minutes' => (int) env('MC_WITHDRAW_TTL_MINUTES', 30),
    ],
];
