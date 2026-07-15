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
            'button_color' => '#2563eb',
            'avatar_color' => '#3b82f6',
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
        $button = self::normalizeHex((string) self::get('button_color'), $defaults['button_color']);
        $avatar = self::normalizeHex((string) self::get('avatar_color'), $defaults['avatar_color']);
        $accent = self::normalizeHex((string) self::get('accent_color'), $defaults['accent_color']);
        $sidebar = self::normalizeHex((string) self::get('sidebar_color'), $defaults['sidebar_color']);
        $sidebarSecondary = self::normalizeHex((string) self::get('sidebar_secondary_color'), $defaults['sidebar_secondary_color']);
        $primaryRgb = self::hexToRgb($primary);
        $primaryHoverRgb = self::hexToRgb($primaryHover);
        $buttonRgb = self::hexToRgb($button);
        $avatarRgb = self::hexToRgb($avatar);
        $accentRgb = self::hexToRgb($accent);

        return <<<CSS
:root {
    --blue-600: {$primary};
    --blue-500: {$primaryHover};
    --button-color: {$button};
    --avatar-color: {$avatar};
    --violet: {$accent};
    --navy: {$sidebar};
    --navy-2: {$sidebarSecondary};
    --brand-primary-rgb: {$primaryRgb};
    --brand-primary-hover-rgb: {$primaryHoverRgb};
    --brand-button-rgb: {$buttonRgb};
    --brand-avatar-rgb: {$avatarRgb};
    --brand-accent-rgb: {$accentRgb};
    --link-color: {$primary};
    --link-hover-color: {$primaryHover};
    --theme-active-gradient: linear-gradient(135deg, {$primary}, {$accent});
    --theme-soft-background: color-mix(in srgb, {$primary} 9%, #FFFFFF);
    --theme-soft-background-strong: color-mix(in srgb, {$primary} 16%, #FFFFFF);
    --muted: color-mix(in srgb, {$primary} 16%, #5F6672);
    --line: color-mix(in srgb, {$primary} 14%, #D9DEE7);
    --page: color-mix(in srgb, {$primary} 5%, #FFFFFF);
    --soft: color-mix(in srgb, {$primary} 4%, #FFFFFF);
    --table-head: color-mix(in srgb, {$primary} 6%, #FFFFFF);
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

.sidebar-footer i {
    color: {$accent} !important;
}

.avatar,
.profile-photo-preview,
.menu-access-avatar {
    background: {$avatar} !important;
    box-shadow: 0 8px 18px rgba({$avatarRgb}, .18);
}

.primary-action,
.modal-submit,
.primary-button,
.btn-primary {
    border-color: {$button} !important;
    background: {$button} !important;
    box-shadow: 0 14px 30px rgba({$buttonRgb}, .2);
}

.primary-action:hover,
.modal-submit:hover,
.primary-button:hover,
.btn-primary:hover {
    filter: brightness(.92);
}
CSS;
    }

    private static function assetUrl(string $path): string
    {
        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return public_storage_url($path);
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
