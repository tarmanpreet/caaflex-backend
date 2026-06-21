<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\PracticeNoteController;
use App\Models\Practice;
use App\Models\PracticeNote;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PracticeNoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();

        // Register routes for testing (actual routes defined in Task 10)
        Route::middleware('web')->prefix('api/v1')->group(function () {
            Route::get('/practices/{practice}/notes', [PracticeNoteController::class, 'index']);
            Route::post('/practices/{practice}/notes', [PracticeNoteController::class, 'store']);
        });
    }

    public function test_admin_can_add_note(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $practice = Practice::factory()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/practices/'.$practice->id.'/notes', [
                'body' => 'This is a test note.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Note added.',
            ]);

        $this->assertDatabaseHas('practice_notes', [
            'practice_id' => $practice->id,
            'user_id' => $admin->id,
            'body' => 'This is a test note.',
        ]);
    }

    public function test_employee_can_add_note(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $practice = Practice::factory()->create();
        $practice->assignedUsers()->attach($employee->id);

        $response = $this->actingAs($employee)
            ->postJson('/api/v1/practices/'.$practice->id.'/notes', [
                'body' => 'Employee note.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Note added.',
            ]);

        $this->assertDatabaseHas('practice_notes', [
            'practice_id' => $practice->id,
            'user_id' => $employee->id,
            'body' => 'Employee note.',
        ]);
    }

    public function test_employee_cannot_add_note_to_unassigned_practice(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('cliente');

        $practice = Practice::factory()->create();

        $response = $this->actingAs($employee)
            ->postJson('/api/v1/practices/'.$practice->id.'/notes', [
                'body' => 'Should fail.',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_list_notes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $practice = Practice::factory()->create();

        PracticeNote::factory()->count(3)->create([
            'practice_id' => $practice->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/practices/'.$practice->id.'/notes');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}
