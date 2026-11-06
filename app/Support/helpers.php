<?php

use App\Support\AppBranding;

if (! function_exists('app_branding')) {
    function app_branding(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? AppBranding::all() : AppBranding::get($key, $default);
    }
}

if (! function_exists('app_brand_name')) {
    function app_brand_name(): string
    {
        return AppBranding::name();
    }
}

if (! function_exists('app_brand_short_name')) {
    function app_brand_short_name(): string
    {
        return AppBranding::shortName();
    }
}

if (! function_exists('app_brand_logo_url')) {
    function app_brand_logo_url(): string
    {
        return AppBranding::logoUrl();
    }
}

if (! function_exists('app_brand_favicon_url')) {
    function app_brand_favicon_url(): string
    {
        return AppBranding::faviconUrl();
    }
}

if (! function_exists('public_storage_url')) {
    function public_storage_url(string $path): string
    {
        return asset('user-files/'.ltrim($path, '/'));
    }
}

if (! function_exists('private_storage_url')) {
    function private_storage_url(string $path): string
    {
        return url('private-storage/'.ltrim($path, '/'));
    }
}

if (! function_exists('pdf_text')) {
    function pdf_text(mixed $value, string $default = '-'): string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return $default;
        }

        if (! preg_match('/(Ã|Â|â[€™€œ€“€])/', $text)) {
            return $text;
        }

        $best = $text;
        $bestScore = preg_match_all('/(Ã|Â|â[€™€œ€“€])/', $text);

        foreach (['ISO-8859-1', 'Windows-1252'] as $encoding) {
            $bytes = @iconv('UTF-8', $encoding.'//IGNORE', $text);

            if ($bytes === false || ! mb_check_encoding($bytes, 'UTF-8')) {
                continue;
            }

            $score = preg_match_all('/(Ã|Â|â[€™€œ€“€])/', $bytes);

            if ($score < $bestScore) {
                $best = $bytes;
                $bestScore = $score;
            }
        }

        return $best;
    }
}
