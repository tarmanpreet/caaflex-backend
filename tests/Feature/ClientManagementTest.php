<?php

namespace Tests\Feature;

use App\Models\ClientProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();
    }

    public function test_admin_can_view_client_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        ClientProfile::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get('/clients')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Clients/Index')
                ->has('clients')
            );
    }

    public function test_cliente_role_cannot_access_client_index(): void
    {
        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');

        $this->actingAs($cliente)
            ->get('/clients')
            ->assertStatus(403);
    }

    public function test_admin_can_create_client_without_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $payload = [
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'phone' => '+39 333 1234567',
            'date_of_birth' => '1990-05-15',
            'fiscal_code' => 'RSSMRA90E15H501Z',
            'email' => 'mario@example.com',
            'create_account' => false,
        ];

        $response = $this->actingAs($admin)
            ->post('/clients', $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('client_profiles', [
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'fiscal_code' => 'RSSMRA90E15H501Z',
            'created_by' => $admin->id,
        ]);

        $profile = ClientProfile::where('fiscal_code', 'RSSMRA90E15H501Z')->first();
        $this->assertNull($profile->user_id);
    }

    public function test_admin_can_create_client_with_portal_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $payload = [
            'first_name' => 'Luigi',
            'last_name' => 'Verdi',
            'phone' => '+39 333 7654321',
            'date_of_birth' => '1985-08-20',
            'create_account' => true,
            'account_email' => 'luigi.verdi@example.com',
        ];

        $response = $this->actingAs($admin)
            ->post('/clients', $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'luigi.verdi@example.com',
        ]);

        $user = User::where('email', 'luigi.verdi@example.com')->first();
        $this->assertTrue($user->hasRole('cliente'));

        $this->assertDatabaseHas('client_profiles', [
            'first_name' => 'Luigi',
            'last_name' => 'Verdi',
            'user_id' => $user->id,
            'created_by' => $admin->id,
        ]);
    }

    public function test_employee_cannot_delete_client(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $profile = ClientProfile::factory()->create();

        $this->actingAs($employee)
            ->delete('/clients/'.$profile->id)
            ->assertStatus(403);
    }

    public function test_admin_can_delete_client(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $profile = ClientProfile::factory()->create();

        $response = $this->actingAs($admin)
            ->delete('/clients/'.$profile->id);

        $response->assertRedirect(route('clients.index'));

        $this->assertDatabaseMissing('client_profiles', [
            'id' => $profile->id,
        ]);
    }

    public function test_show_page_renders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $profile = ClientProfile::factory()->create();

        $this->actingAs($admin)
            ->get('/clients/'.$profile->id)
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Clients/Show')
            );
    }

    public function test_admin_can_access_create_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/clients/create')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Clients/Create')
            );
    }

    public function test_admin_can_access_edit_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $profile = ClientProfile::factory()->create();

        $this->actingAs($admin)
            ->get('/clients/'.$profile->id.'/edit')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Clients/Edit')
            );
    }
}
