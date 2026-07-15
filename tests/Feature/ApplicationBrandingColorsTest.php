<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AppBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationBrandingColorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_customize_button_and_avatar_colors(): void
    {
        $superadmin = User::query()->create([
            'name' => 'Theme administrator',
            'email' => 'theme-admin@example.test',
            'password' => 'StrongPass@123',
            'role' => User::ROLE_SUPERADMIN,
        ]);

        $this->actingAs($superadmin)
            ->get(route('admin.application-settings'))
            ->assertOk()
            ->assertSee('name="button_color"', false)
            ->assertSee('name="avatar_color"', false);

        $this->actingAs($superadmin)
            ->put(route('admin.application-settings.update'), [
                'app_name' => 'Themed ERP',
                'short_name' => 'ERP',
                'primary_color' => '#4A2508',
                'primary_hover_color' => '#9A7610',
                'button_color' => '#6B3F12',
                'avatar_color' => '#B07D21',
                'accent_color' => '#A18416',
                'sidebar_color' => '#321705',
                'sidebar_secondary_color' => '#5B3109',
            ])
            ->assertRedirect(route('admin.application-settings'));

        $this->assertDatabaseHas('application_settings', [
            'key' => 'button_color',
            'value' => '#6B3F12',
        ]);
        $this->assertDatabaseHas('application_settings', [
            'key' => 'avatar_color',
            'value' => '#B07D21',
        ]);

        AppBranding::clearCache();
        $themeCss = AppBranding::themeCss();

        $this->assertStringContainsString('--button-color: #6B3F12', $themeCss);
        $this->assertStringContainsString('--avatar-color: #B07D21', $themeCss);
        $this->assertStringContainsString('--link-color: #4A2508', $themeCss);
        $this->assertStringContainsString('--theme-active-gradient: linear-gradient(135deg, #4A2508, #A18416)', $themeCss);
        $this->assertStringContainsString('--theme-soft-background:', $themeCss);
        $this->assertStringContainsString('--page: color-mix(in srgb, #4A2508 5%, #FFFFFF)', $themeCss);

        $mainCss = file_get_contents(resource_path('css/main.css'));
        $this->assertStringContainsString('color: var(--link-color);', $mainCss);
        $this->assertStringContainsString('background: rgba(var(--brand-accent-rgb), .22);', $mainCss);
        $this->assertStringContainsString('background: var(--theme-soft-background);', $mainCss);
    }
}
