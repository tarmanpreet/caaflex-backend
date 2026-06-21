<?php

namespace Tests\Feature;

use App\Models\PracticeType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $employee;

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
    }

    public function test_admin_can_create_practice_type(): void
    {
        $payload = [
            'name' => 'TIPO_TEST_UNICO',
            'duration_minutes' => 45,
            'color' => '#FF5733',
        ];

        $this->actingAs($this->admin)
            ->post('/practice-types', $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('practice_types', [
            'name' => 'TIPO_TEST_UNICO',
            'duration_minutes' => 45,
            'color' => '#FF5733',
        ]);
    }

    public function test_admin_can_update_practice_type(): void
    {
        $practiceType = PracticeType::factory()->create([
            'name' => 'ORIGINAL_NAME',
        ]);

        $this->actingAs($this->admin)
            ->put('/practice-types/'.$practiceType->id, [
                'name' => 'UPDATED_NAME',
                'duration_minutes' => 90,
                'color' => '#00FF00',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('practice_types', [
            'id' => $practiceType->id,
            'name' => 'UPDATED_NAME',
            'duration_minutes' => 90,
        ]);
    }

    public function test_admin_can_delete_practice_type(): void
    {
        $practiceType = PracticeType::factory()->create([
            'name' => 'TO_DELETE',
        ]);

        $this->actingAs($this->admin)
            ->delete('/practice-types/'.$practiceType->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('practice_types', [
            'id' => $practiceType->id,
        ]);
    }

    public function test_employee_cannot_create_practice_type(): void
    {
        $this->actingAs($this->employee)
            ->post('/practice-types', [
                'name' => 'UNAUTHORIZED',
                'duration_minutes' => 30,
                'color' => '#000000',
            ])
            ->assertStatus(403);
    }

    public function test_all_users_can_view_practice_types(): void
    {
        PracticeType::factory()->create(['name' => 'VIEWABLE_TYPE']);

        $this->actingAs($this->admin)
            ->get('/practice-types')
            ->assertStatus(200);

        $this->actingAs($this->employee)
            ->get('/practice-types')
            ->assertStatus(200);
    }
}
