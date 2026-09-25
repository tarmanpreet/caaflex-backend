<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    public function test_brand_name_falls_back_to_application_name(): void
    {
        $this->assertSame(config('app.name'), config('branding.name'));
    }

    public function test_default_branding_uses_the_mobile_app_assets(): void
    {
        $this->assertSame('/brand/caaflex-logo.png', config('branding.logo_url'));
        $this->assertSame('/brand/caaflex-logo-light.png', config('branding.logo_light_url'));
        $this->assertSame('/brand/caaflex-mark.png', config('branding.mark_url'));
        $this->assertSame('/brand/caaflex-mark.png', config('branding.favicon_url'));
        $this->assertFileExists(public_path('brand/caaflex-logo.png'));
        $this->assertFileExists(public_path('brand/caaflex-logo-light.png'));
        $this->assertFileExists(public_path('brand/caaflex-mark.png'));
    }

    public function test_branding_configuration_is_shared_with_inertia_pages(): void
    {
        $this->withoutVite();

        config()->set('branding', [
            'name' => 'Cliente Demo',
            'logo_url' => '/customer-brand/cliente-demo-logo.svg',
            'logo_light_url' => '/customer-brand/cliente-demo-logo-light.svg',
            'mark_url' => '/customer-brand/cliente-demo-mark.svg',
            'favicon_url' => '/customer-brand/cliente-demo-favicon.ico',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="application-name" content="Cliente Demo">', false)
            ->assertSee('<link rel="icon" href="/customer-brand/cliente-demo-favicon.ico">', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('branding.name', 'Cliente Demo')
                ->where('branding.logo_url', '/customer-brand/cliente-demo-logo.svg')
                ->where('branding.logo_light_url', '/customer-brand/cliente-demo-logo-light.svg')
                ->where('branding.mark_url', '/customer-brand/cliente-demo-mark.svg')
                ->where('branding.favicon_url', '/customer-brand/cliente-demo-favicon.ico')
            );
    }
}
