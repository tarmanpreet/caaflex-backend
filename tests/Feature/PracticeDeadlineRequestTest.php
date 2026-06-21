<?php

namespace Tests\Feature;

use App\Models\Practice;
use App\Models\PracticeDeadline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PracticeDeadlineRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $assignedUser;
    protected User $unassignedUser;
    protected Practice $practice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->assignedUser = User::factory()->create();
        $this->assignedUser->assignRole('employee');

        $this->unassignedUser = User::factory()->create();
        $this->unassignedUser->assignRole('employee');

        $this->practice = Practice::factory()->create();
        $this->practice->assignedUsers()->attach($this->assignedUser->id);
    }

    protected function createStoreRequest(): \App\Http\Requests\StorePracticeDeadlineRequest
    {
        $request = new \App\Http\Requests\StorePracticeDeadlineRequest();
        
        $request->setRouteResolver(function () {
            $route = $this->createMock(\Illuminate\Routing\Route::class);
            $route->method('parameter')->with('practice')->willReturn($this->practice);
            return $route;
        });

        return $request;
    }

    public function test_valid_payload_passes_all_rules(): void
    {
        $data = [
            'title' => 'Complete tax form',
            'notes' => 'Important deadline',
            'deadline_at' => '2026-04-15',
            'status' => PracticeDeadline::STATUS_PENDING,
            'priority' => PracticeDeadline::PRIORITY_HIGH,
            'user_id' => $this->assignedUser->id,
        ];

        $request = $this->createStoreRequest($data);
        $validator = Validator::make($data, $request->rules());
        
        $this->assertFalse($validator->fails(), $validator->errors()->toJson());
    }

    public function test_missing_required_fields_returns_errors(): void
    {
        $data = [];

        $request = $this->createStoreRequest($data);
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertArrayHasKey('deadline_at', $validator->errors()->toArray());
    }

    public function test_malformed_deadline_at_fails(): void
    {
        $data = [
            'title' => 'Test deadline',
            'deadline_at' => 'not-a-date',
        ];

        $request = $this->createStoreRequest($data);
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('deadline_at', $validator->errors()->toArray());
    }

    public function test_user_id_not_assigned_to_practice_fails(): void
    {
        $data = [
            'title' => 'Test deadline',
            'deadline_at' => '2026-04-15',
            'user_id' => $this->unassignedUser->id,
        ];

        $request = $this->createStoreRequest($data);
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    public function test_user_id_assigned_to_practice_passes(): void
    {
        $data = [
            'title' => 'Test deadline',
            'deadline_at' => '2026-04-15',
            'user_id' => $this->assignedUser->id,
        ];

        $request = $this->createStoreRequest($data);
        $validator = Validator::make($data, $request->rules());
        
        $this->assertFalse($validator->fails(), $validator->errors()->toJson());
    }

    public function test_invalid_status_fails(): void
    {
        $data = [
            'title' => 'Test deadline',
            'deadline_at' => '2026-04-15',
            'status' => 'invalid_status',
        ];

        $request = $this->createStoreRequest($data);
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_invalid_priority_fails(): void
    {
        $data = [
            'title' => 'Test deadline',
            'deadline_at' => '2026-04-15',
            'priority' => 5,
        ];

        $request = $this->createStoreRequest($data);
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('priority', $validator->errors()->toArray());
    }

    public function test_priority_zero_fails(): void
    {
        $data = [
            'title' => 'Test deadline',
            'deadline_at' => '2026-04-15',
            'priority' => 0,
        ];

        $request = $this->createStoreRequest($data);
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('priority', $validator->errors()->toArray());
    }

    public function test_title_max_length_fails(): void
    {
        $data = [
            'title' => str_repeat('a', 256),
            'deadline_at' => '2026-04-15',
        ];

        $request = $this->createStoreRequest($data);
        $validator = Validator::make($data, $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    public function test_all_statuses_are_valid(): void
    {
        $statuses = [
            PracticeDeadline::STATUS_PENDING,
            PracticeDeadline::STATUS_IN_PROGRESS,
            PracticeDeadline::STATUS_COMPLETED,
            PracticeDeadline::STATUS_CANCELLED,
        ];

        foreach ($statuses as $status) {
            $data = [
                'title' => 'Test deadline',
                'deadline_at' => '2026-04-15',
                'status' => $status,
            ];

            $request = $this->createStoreRequest($data);
            $validator = Validator::make($data, $request->rules());
            
            $this->assertFalse($validator->fails(), "Status {$status} should be valid: " . $validator->errors()->toJson());
        }
    }

    public function test_update_request_all_fields_nullable(): void
    {
        $request = new \App\Http\Requests\UpdatePracticeDeadlineRequest();
        
        $request->setRouteResolver(function () {
            $route = $this->createMock(\Illuminate\Routing\Route::class);
            $route->method('parameter')->with('practice')->willReturn($this->practice);
            return $route;
        });

        $data = [];
        $validator = Validator::make($data, $request->rules());
        
        $this->assertFalse($validator->fails(), $validator->errors()->toJson());
    }
}
