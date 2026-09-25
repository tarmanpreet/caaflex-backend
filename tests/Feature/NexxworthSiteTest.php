<?php

namespace Tests\Feature;

use App\Mail\NexxworthContactMail;
use App\Models\Practice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NexxworthSiteTest extends TestCase
{
    use RefreshDatabase;

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
        $this->get(route('nexxworth.en.home'))->assertNotFound();
        $this->get(route('nexxworth.status'))->assertNotFound();
        $this->post(route('nexxworth.status.lookup'), ['code' => 'ABCDEFGHIJ'])->assertNotFound();
        $this->get(route('nexxworth.sitemap'))->assertNotFound();
        $this->get(route('robots'))->assertOk()->assertDontSee('Sitemap:');
    }

    public function test_english_pages_have_distinct_localized_metadata_and_alternates(): void
    {
        $this->withoutVite();
        config()->set('branding.customer_code', 'nexxworth');

        foreach (['home' => 'Home', 'about' => 'About', 'services' => 'Services', 'partners' => 'Partners', 'contact' => 'Contact', 'privacy' => 'Privacy'] as $key => $component) {
            $route = "nexxworth.en.{$key}";
            $italianRoute = $key === 'home' ? 'home' : "nexxworth.{$key}";

            $this->get(route($route))
                ->assertOk()
                ->assertSee('<html lang="en">', false)
                ->assertSee('<link rel="canonical" href="'.route($route).'">', false)
                ->assertSee('<link rel="alternate" hreflang="it" href="'.route($italianRoute).'">', false)
                ->assertSee('<link rel="alternate" hreflang="en" href="'.route($route).'">', false)
                ->assertSee('<meta name="description"', false)
                ->assertInertia(fn (Assert $page) => $page->component("Public/Nexxworth/En/{$component}"));

            $this->get(route($italianRoute))
                ->assertOk()
                ->assertSee('<html lang="it">', false)
                ->assertSee('<link rel="alternate" hreflang="en" href="'.route($route).'">', false);
        }
    }

    public function test_sitemap_lists_both_languages_and_tracking_pages(): void
    {
        config()->set('branding.customer_code', 'nexxworth');

        $response = $this->get(route('nexxworth.sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('nexxworth.en.home'))
            ->assertSee(route('nexxworth.en.status'))
            ->assertSee(route('nexxworth.status'));

        $xml = new \DOMDocument;
        $this->assertTrue($xml->loadXML($response->getContent()));
        $this->assertSame(14, $xml->getElementsByTagName('url')->length);

        $this->get(route('robots'))
            ->assertOk()
            ->assertSee('Sitemap: '.route('nexxworth.sitemap'));
    }

    public function test_english_contact_form_returns_to_english_page(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        config()->set('branding.customer_code', 'nexxworth');

        $this->post(route('nexxworth.en.contact.submit'), [
            'name' => 'Alex Smith',
            'email' => 'alex@example.com',
            'message' => 'I would like information about a visa application.',
            'privacy_accepted' => true,
        ])->assertRedirect(route('nexxworth.en.contact'))
            ->assertSessionHas('success', 'Message sent. We will get back to you as soon as possible.');

        Mail::assertSent(NexxworthContactMail::class);
    }

    public function test_nexxworth_tracking_uses_its_own_pages_and_exposes_only_status(): void
    {
        $this->withoutVite();
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('branding.customer_code', 'nexxworth');
        $practice = Practice::factory()->create(['status' => 'in_lavorazione']);

        $this->get(route('nexxworth.status'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Nexxworth/Status')->where('locale', 'it'));

        $this->get(route('nexxworth.en.status'))
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertInertia(fn (Assert $page) => $page->component('Public/Nexxworth/Status')->where('locale', 'en'));

        $this->post(route('nexxworth.en.status.lookup'), ['code' => strtolower($practice->tracking_code)])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Nexxworth/Status')
                ->where('locale', 'en')
                ->where('result.code', $practice->tracking_code)
                ->where('result.status', 'in_lavorazione')
                ->missing('result.client')
                ->missing('result.documents'));
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
