<?php

namespace Tests\Feature;

use App\Models\Practice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PublicPracticeStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        RateLimiter::clear(sha1('|127.0.0.1'));
        RateLimiter::clear(sha1('|203.0.113.50'));
    }

    public function test_guest_can_open_public_practice_status_page(): void
    {
        $this->get(route('practice-status.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PracticeStatus')
                ->missing('result')
            );
    }

    public function test_practices_receive_a_unique_ten_character_tracking_code(): void
    {
        $practices = Practice::factory()->count(15)->create();

        $codes = $practices->pluck('tracking_code');

        $this->assertCount(15, $codes->unique());
        $codes->each(fn (string $code) => $this->assertMatchesRegularExpression('/^[A-Z0-9]{10}$/', $code));
    }

    public function test_guest_receives_only_tracking_code_and_status_for_a_valid_lookup(): void
    {
        $practice = Practice::factory()->create(['status' => 'in_lavorazione']);

        $this->post(route('practice-status.lookup'), [
            'code' => strtolower($practice->tracking_code),
        ])->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PracticeStatus')
                ->where('result.code', $practice->tracking_code)
                ->where('result.status', 'in_lavorazione')
                ->missing('result.id')
                ->missing('result.client')
                ->missing('result.branch')
                ->missing('result.notes')
                ->missing('result.documents')
                ->missing('result.created_at')
            );
    }

    public function test_unknown_tracking_code_does_not_expose_practice_data(): void
    {
        Practice::factory()->create();

        $this->post(route('practice-status.lookup'), [
            'code' => 'ZZZZZZZZZZ',
        ])->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PracticeStatus')
                ->where('result', null)
                ->has('lookupError')
                ->missing('practice')
                ->missing('client')
            );
    }

    public function test_lookup_requires_a_ten_character_alphanumeric_code(): void
    {
        $this->from(route('practice-status.index'))
            ->post(route('practice-status.lookup'), ['code' => 'ABC!'])
            ->assertRedirect(route('practice-status.index'))
            ->assertSessionHasErrors('code');
    }

    public function test_public_lookup_is_rate_limited(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50']);

        foreach (range(1, 10) as $attempt) {
            $this->post(route('practice-status.lookup'), ['code' => 'ZZZZZZZZZZ'])
                ->assertOk();
        }

        $this->post(route('practice-status.lookup'), ['code' => 'ZZZZZZZZZZ'])
            ->assertTooManyRequests();
    }
}
