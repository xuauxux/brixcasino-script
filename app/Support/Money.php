<?php

namespace App\Support;

/**
 * Virtual currency formatting and the shared minimum bet.
 *
 * Everything the player sees is dollars ("$10,000"), and every game rejects
 * the same floor, so the text and the rule can never drift apart.
 */
final class Money
{
    public const SYMBOL = '$';

    public static function minBet(): float
    {
        $min = (float) config('donut.min_bet', 10000);

        return $min > 0 ? $min : 1.0;
    }

    public static function format(float $amount, int $decimals = 0): string
    {
        return self::SYMBOL.number_format($amount, $decimals);
    }

    public static function minBetMessage(): string
    {
        return 'The minimum bet is '.self::format(self::minBet());
    }

    /** True when the submitted bet is missing, not a number, or under the floor. */
    public static function tooSmall($bet): bool
    {
        if (! is_numeric($bet)) {
            return true;
        }

        return (float) $bet < self::minBet();
    }
}
