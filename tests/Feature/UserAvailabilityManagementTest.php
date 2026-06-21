<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserAvailabilityManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $employee;

    protected User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->employee = User::factory()->create();
        $this->employee->assignRole('employee');

        $this->targetUser = User::factory()->create();
        $this->targetUser->assignRole('employee');
    }

    public function test_admin_can_manage_user_availabilities(): void
    {
        $this->actingAs($this->admin)
            ->get('/users/'.$this->targetUser->id.'/availabilities')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('UserAvailabilities/Index')
                ->has('targetUser')
                ->has('availabilities')
                ->has('days')
            );

        $this->actingAs($this->admin)
            ->post('/users/'.$this->targetUser->id.'/availabilities', [
                'day_of_week' => 1,
                'time_from' => '09:00',
                'time_to' => '17:00',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_availabilities', [
            'user_id' => $this->targetUser->id,
            'day_of_week' => 1,
            'time_from' => '09:00',
            'time_to' => '17:00',
        ]);
    }

    public function test_employee_cannot_manage_availabilities(): void
    {
        $this->actingAs($this->employee)
            ->get('/users/'.$this->targetUser->id.'/availabilities')
            ->assertStatus(403);

        $this->actingAs($this->employee)
            ->post('/users/'.$this->targetUser->id.'/availabilities', [
                'day_of_week' => 1,
                'time_from' => '09:00',
                'time_to' => '17:00',
            ])
            ->assertStatus(403);
    }
}
