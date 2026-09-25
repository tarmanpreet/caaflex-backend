<?php

namespace Tests\Feature;

use App\Mail\NexxworthContactMail;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NexxworthSiteTest extends TestCase
{
    public function test_caaflex_remains_the_default_public_site(): void
    {
        $this->withoutVite();
        config()->set('branding.customer_code', 'caaflex');

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Welcome'));

        $this->get(route('nexxworth.about'))->assertNotFound();
        $this->get(route('nexxworth.services'))->assertNotFound();
        $this->get(route('nexxworth.partners'))->assertNotFound();
        $this->get(route('nexxworth.contact'))->assertNotFound();
        $this->get(route('nexxworth.privacy'))->assertNotFound();
    }

    public function test_nexxworth_installation_serves_all_its_public_pages(): void
    {
        $this->withoutVite();
        config()->set('branding.customer_code', 'nexxworth');

        foreach ([
            'home' => 'Home',
            'nexxworth.about' => 'About',
            'nexxworth.services' => 'Services',
            'nexxworth.partners' => 'Partners',
            'nexxworth.contact' => 'Contact',
            'nexxworth.privacy' => 'Privacy',
        ] as $routeName => $component) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component("Public/Nexxworth/{$component}"));
        }
    }

    public function test_nexxworth_branding_is_shared_with_the_site_and_mobile_configuration(): void
    {
        $this->withoutVite();
        config()->set('branding', [
            'customer_code' => 'nexxworth',
            'name' => 'Nexxworth Consulting',
            'logo_url' => '/brand/nexxworth/logo.png',
            'logo_light_url' => '/brand/nexxworth/logo.png',
            'mark_url' => '/brand/nexxworth/mark.png',
            'favicon_url' => '/brand/nexxworth/mark.png',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="application-name" content="Nexxworth Consulting">', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('branding.customer_code', 'nexxworth')
                ->where('branding.logo_url', '/brand/nexxworth/logo.png'));

        $this->get(route('api.app-config'))
            ->assertOk()
            ->assertJsonPath('data.branding.name', 'Nexxworth Consulting')
            ->assertJsonPath('data.branding.logo_url', '/brand/nexxworth/logo.png');

        $this->assertFileExists(public_path('brand/nexxworth/logo.png'));
        $this->assertFileExists(public_path('brand/nexxworth/mark.png'));
    }

    public function test_contact_form_sends_a_message_to_nexxworth(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        config()->set('branding.customer_code', 'nexxworth');
        config()->set('branding.contact_email', 'ksd.servizi@gmail.com');

        $this->post(route('nexxworth.contact.submit'), [
            'name' => 'Maria Rossi',
            'email' => 'maria@example.com',
            'phone' => '3331234567',
            'message' => 'Vorrei informazioni sul ricongiungimento familiare.',
            'privacy_accepted' => true,
            'website' => '',
        ])->assertRedirect(route('nexxworth.contact'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        Mail::assertSent(NexxworthContactMail::class, fn (NexxworthContactMail $mail): bool => $mail->hasTo('ksd.servizi@gmail.com')
            && $mail->contactData['name'] === 'Maria Rossi'
            && $mail->contactData['message'] === 'Vorrei informazioni sul ricongiungimento familiare.'
            && str_contains($mail->render(), 'Maria Rossi'));
    }

    public function test_contact_form_rejects_invalid_input_and_is_unavailable_to_caaflex(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        config()->set('branding.customer_code', 'nexxworth');

        $this->post(route('nexxworth.contact.submit'), [
            'name' => '',
            'email' => 'not-an-email',
            'message' => 'short',
            'privacy_accepted' => false,
        ])->assertSessionHasErrors(['name', 'email', 'message', 'privacy_accepted']);

        config()->set('branding.customer_code', 'caaflex');

        $this->post(route('nexxworth.contact.submit'), [
            'name' => 'Maria Rossi',
            'email' => 'maria@example.com',
            'message' => 'Vorrei informazioni sul ricongiungimento familiare.',
            'privacy_accepted' => true,
        ])->assertForbidden();

        Mail::assertNothingSent();
    }
}
