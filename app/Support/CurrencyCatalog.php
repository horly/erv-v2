<?php

namespace App\Support;

class CurrencyCatalog
{
    public static function all(): array
    {
        return config('currencies');
    }

    public static function sorted(?string $locale = null): array
    {
        $localeKey = self::nameKey($locale);
        $currencies = self::all();

        uasort($currencies, fn (array $first, array $second): int => strnatcasecmp(
            $first[$localeKey] ?? $first['name_fr'],
            $second[$localeKey] ?? $second['name_fr'],
        ));

        return $currencies;
    }

    public static function label(string $code, ?string $locale = null): string
    {
        $currencies = self::all();
        $currency = $currencies[$code] ?? null;

        if (! $currency) {
            return $code;
        }

        $name = $currency[self::nameKey($locale)] ?? $currency['name_fr'];
        $symbol = $currency['symbol'] ?? null;

        return $symbol ? "{$name} ({$code} - {$symbol})" : "{$name} ({$code})";
    }

    private static function nameKey(?string $locale = null): string
    {
        return 'name_'.($locale ?: app()->getLocale());
    }
}
