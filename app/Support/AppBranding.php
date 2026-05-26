<?php

namespace App\Support;

use App\Models\ApplicationSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AppBranding
{
    public const CACHE_KEY = 'application_branding';

    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $defaults = self::defaults();

            try {
                if (! Schema::hasTable('application_settings')) {
                    return $defaults;
                }

                $settings = ApplicationSetting::query()
                    ->whereIn('key', array_keys($defaults))
                    ->pluck('value', 'key')
                    ->all();
            } catch (Throwable) {
                return $defaults;
            }

            return array_replace($defaults, array_filter($settings, fn ($value) => filled($value)));
        });
    }

    public static function defaults(): array
    {
        return [
            'app_name' => config('app.name', 'EXAD ERP'),
            'short_name' => 'EXAD ERP',
            'tagline' => 'Solution & Services',
            'description' => 'Une plateforme ERP unifiée pour la finance, les ressources humaines, les opérations et la relation client.',
            'logo_path' => 'img/logo/exad-1200x1200.jpg',
            'favicon_path' => null,
            'support_email' => null,
            'support_phone' => null,
            'website' => null,
            'primary_color' => '#2563eb',
            'primary_hover_color' => '#3b82f6',
            'accent_color' => '#7c3aed',
            'sidebar_color' => '#071b42',
            'sidebar_secondary_color' => '#0d2b61',
            'copyright' => '© '.date('Y').' EXAD ERP - Tous droits réservés.',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function name(): string
    {
        return (string) self::get('app_name', 'EXAD ERP');
    }

    public static function shortName(): string
    {
        return (string) self::get('short_name', self::name());
    }

    public static function logoUrl(): string
    {
        return self::assetUrl((string) self::get('logo_path', 'img/logo/exad-1200x1200.jpg'));
    }

    public static function faviconUrl(): string
    {
        $favicon = self::get('favicon_path');

        return filled($favicon) ? self::assetUrl((string) $favicon) : self::logoUrl();
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function themeCss(): string
    {
        $defaults = self::defaults();
        $primary = self::normalizeHex((string) self::get('primary_color'), $defaults['primary_color']);
        $primaryHover = self::normalizeHex((string) self::get('primary_hover_color'), $defaults['primary_hover_color']);
        $accent = self::normalizeHex((string) self::get('accent_color'), $defaults['accent_color']);
        $sidebar = self::normalizeHex((string) self::get('sidebar_color'), $defaults['sidebar_color']);
        $sidebarSecondary = self::normalizeHex((string) self::get('sidebar_secondary_color'), $defaults['sidebar_secondary_color']);
        $primaryRgb = self::hexToRgb($primary);
        $primaryHoverRgb = self::hexToRgb($primaryHover);
        $accentRgb = self::hexToRgb($accent);

        return <<<CSS
:root {
    --blue-600: {$primary};
    --blue-500: {$primaryHover};
    --violet: {$accent};
    --navy: {$sidebar};
    --navy-2: {$sidebarSecondary};
    --brand-primary-rgb: {$primaryRgb};
    --brand-primary-hover-rgb: {$primaryHoverRgb};
    --brand-accent-rgb: {$accentRgb};
    --shadow: 0 22px 50px rgba({$primaryRgb}, .12);
}

.dashboard-sidebar {
    background: linear-gradient(180deg, var(--navy) 0%, var(--navy-2) 100%) !important;
}

.nav-link:hover,
.nav-link.active {
    background: linear-gradient(90deg, rgba({$primaryRgb}, .62), rgba({$primaryRgb}, .18));
}

.nav-link.active,
.sidebar-collapsed .nav-link.active {
    box-shadow: inset 3px 0 0 {$accent};
}
CSS;
    }

    private static function assetUrl(string $path): string
    {
        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return asset($path);
    }

    private static function normalizeHex(string $value, string $fallback): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtoupper($value) : strtoupper($fallback);
    }

    private static function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');

        return hexdec(substr($hex, 0, 2)).', '.hexdec(substr($hex, 2, 2)).', '.hexdec(substr($hex, 4, 2));
    }
}
