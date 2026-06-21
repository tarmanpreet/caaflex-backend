<?php

namespace Tests\Feature;

use App\Models\ClientProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InviteClientUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_creates_new_user_and_links_to_client_profile(): void
    {
        $admin = $this->makeAdmin();
        $client = ClientProfile::factory()->create(['user_id' => null, 'email' => 'nuovo@example.com']);

        $this->actingAs($admin)
            ->post(route('clients.invite-user', $client))
            ->assertRedirect(route('clients.show', $client));

        $this->assertDatabaseHas('users', ['email' => 'nuovo@example.com']);

        $user = User::where('email', 'nuovo@example.com')->first();
        $this->assertTrue($user->hasRole('cliente'));
        $this->assertDatabaseHas('client_profiles', ['id' => $client->id, 'user_id' => $user->id]);
    }

    public function test_links_existing_user_without_profile_to_client(): void
    {
        $admin = $this->makeAdmin();
        $existingUser = User::factory()->create(['email' => 'esiste@example.com']);
        $client = ClientProfile::factory()->create(['user_id' => null, 'email' => 'esiste@example.com']);

        $this->actingAs($admin)
            ->post(route('clients.invite-user', $client))
            ->assertRedirect(route('clients.show', $client));

        $this->assertDatabaseHas('client_profiles', ['id' => $client->id, 'user_id' => $existingUser->id]);
    }

    public function test_returns_error_if_email_belongs_to_user_with_another_profile(): void
    {
        $admin = $this->makeAdmin();
        $otherUser = User::factory()->create(['email' => 'altro@example.com']);
        $otherUser->assignRole('cliente');
        ClientProfile::factory()->create(['user_id' => $otherUser->id]);

        $client = ClientProfile::factory()->create(['user_id' => null, 'email' => 'altro@example.com']);

        $this->actingAs($admin)
            ->post(route('clients.invite-user', $client))
            ->assertSessionHasErrors('invite_email');

        $this->assertDatabaseHas('client_profiles', ['id' => $client->id, 'user_id' => null]);
    }

    public function test_returns_error_if_client_has_no_email(): void
    {
        $admin = $this->makeAdmin();
        $client = ClientProfile::factory()->create(['user_id' => null, 'email' => null]);

        $this->actingAs($admin)
            ->post(route('clients.invite-user', $client))
            ->assertSessionHasErrors('invite_email');
    }

    public function test_employee_cannot_invite_user(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $client = ClientProfile::factory()->create(['user_id' => null, 'email' => 'test@example.com']);

        $this->actingAs($employee)
            ->post(route('clients.invite-user', $client))
            ->assertStatus(403);
    }

    public function test_users_index_excludes_cliente_role(): void
    {
        $admin = $this->makeAdmin();
        $clienteUser = User::factory()->create(['name' => 'Mario Cliente']);
        $clienteUser->assignRole('cliente');
        $employeeUser = User::factory()->create(['name' => 'Anna Employee']);
        $employeeUser->assignRole('employee');

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Users/Index')
                ->where('users.data', fn ($data) => collect($data)->every(
                    fn ($u) => ! collect($u['roles'])->contains('name', 'cliente')
                ))
            );
    }
}
