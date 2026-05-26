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
