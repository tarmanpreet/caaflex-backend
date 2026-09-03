<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FlashFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_feedback_is_shared_with_every_inertia_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutVite();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->withSession(['success' => 'Dati aggiornati.'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('flash.success', 'Dati aggiornati.')
                ->where('flash.error', null)
            );
    }
}
