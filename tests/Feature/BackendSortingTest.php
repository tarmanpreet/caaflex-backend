<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\ClientProfile;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\Procedure;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendSortingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->withoutVite();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_clients_index_sorts_by_city_backend(): void
    {
        ClientProfile::factory()->create(['first_name' => 'A', 'last_name' => 'Z', 'city' => 'Roma']);
        ClientProfile::factory()->create(['first_name' => 'B', 'last_name' => 'Y', 'city' => 'Milano']);
        ClientProfile::factory()->create(['first_name' => 'C', 'last_name' => 'X', 'city' => 'Napoli']);

        $response = $this->actingAs($this->admin)
            ->get(route('clients.index', ['sort' => 'city', 'direction' => 'asc']))
            ->assertInertia(fn ($page) => $page
                ->component('Clients/Index')
                ->where('filters.sort', 'city')
                ->where('filters.direction', 'asc')
            );

        $cities = array_map(fn ($c) => $c['city'], $response->viewData('page')['props']['clients']['data']);
        $sorted = $cities;
        sort($sorted);
        $this->assertSame($sorted, $cities);
    }

    public function test_clients_index_search_filters_backend(): void
    {
        ClientProfile::factory()->create(['first_name' => 'Mario', 'last_name' => 'Rossi', 'fiscal_code' => 'RSSMRA80A01H501Z']);
        ClientProfile::factory()->create(['first_name' => 'Luigi', 'last_name' => 'Bianchi', 'fiscal_code' => 'BNCLGU80A01H501Z']);

        $response = $this->actingAs($this->admin)
            ->get(route('clients.index', ['search' => 'Rossi']));

        $data = $response->viewData('page')['props']['clients']['data'];
        $this->assertCount(1, $data);
        $this->assertSame('Rossi', $data[0]['last_name']);
    }

    public function test_practices_index_sorts_by_id_desc_backend(): void
    {
        $client = ClientProfile::factory()->create();
        Practice::factory()->create(['client_profile_id' => $client->id, 'type' => '730']);
        Practice::factory()->create(['client_profile_id' => $client->id, 'type' => 'ISEE']);
        Practice::factory()->create(['client_profile_id' => $client->id, 'type' => 'RED']);

        $response = $this->actingAs($this->admin)
            ->get(route('practices.index', ['sort' => 'id', 'direction' => 'desc']))
            ->assertInertia(fn ($page) => $page
                ->component('Practices/Index')
                ->where('filters.sort', 'id')
                ->where('filters.direction', 'desc')
            );

        $ids = array_map(fn ($p) => $p['id'], $response->viewData('page')['props']['practices']['data']);
        $sorted = $ids;
        rsort($sorted);
        $this->assertSame($sorted, $ids);
    }

    public function test_practices_index_status_filter_backend(): void
    {
        $client = ClientProfile::factory()->create();
        Practice::factory()->create(['client_profile_id' => $client->id, 'status' => 'nuova']);
        Practice::factory()->create(['client_profile_id' => $client->id, 'status' => 'completata']);
        Practice::factory()->create(['client_profile_id' => $client->id, 'status' => 'nuova']);

        $response = $this->actingAs($this->admin)
            ->get(route('practices.index', ['status' => 'nuova']));

        $data = $response->viewData('page')['props']['practices']['data'];
        $this->assertCount(2, $data);
        foreach ($data as $p) {
            $this->assertSame('nuova', $p['status']);
        }
    }

    public function test_appointments_index_sorts_by_scheduled_at_backend(): void
    {
        $client = ClientProfile::factory()->create();
        $practiceType = PracticeType::factory()->create();
        Appointment::factory()->create([
            'client_profile_id' => $client->id,
            'practice_type_id' => $practiceType->id,
            'scheduled_at' => '2025-03-01 10:00:00',
        ]);
        Appointment::factory()->create([
            'client_profile_id' => $client->id,
            'practice_type_id' => $practiceType->id,
            'scheduled_at' => '2025-01-15 09:00:00',
        ]);
        Appointment::factory()->create([
            'client_profile_id' => $client->id,
            'practice_type_id' => $practiceType->id,
            'scheduled_at' => '2025-02-20 14:00:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('appointments.index', ['sort' => 'scheduled_at', 'direction' => 'asc', 'view' => 'lista']))
            ->assertInertia(fn ($page) => $page
                ->where('filters.sort', 'scheduled_at')
                ->where('filters.direction', 'asc')
            );

        $dates = array_map(fn ($a) => $a['scheduled_at'], $response->viewData('page')['props']['appointments']['data']);
        $sorted = $dates;
        sort($sorted);
        $this->assertSame($sorted, $dates);
    }

    public function test_branches_index_search_and_sort_backend(): void
    {
        Branch::factory()->create(['name' => 'Roma Centro', 'city' => 'Roma', 'is_active' => true]);
        Branch::factory()->create(['name' => 'Milano Nord', 'city' => 'Milano', 'is_active' => false]);
        Branch::factory()->create(['name' => 'Napoli Est', 'city' => 'Napoli', 'is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->get(route('branches.index', ['search' => 'Milano', 'sort' => 'city', 'direction' => 'asc']))
            ->assertInertia(fn ($page) => $page
                ->where('filters.search', 'Milano')
                ->where('filters.sort', 'city')
            );

        $data = $response->viewData('page')['props']['branches'];
        $this->assertCount(1, $data);
        $this->assertSame('Milano Nord', $data[0]['name']);
    }

    public function test_procedures_index_search_backend(): void
    {
        PracticeType::factory()->create();
        Procedure::factory()->create(['name' => '730 Standard']);
        Procedure::factory()->create(['name' => 'ISEE Base']);
        Procedure::factory()->create(['name' => '730 Plus']);

        $response = $this->actingAs($this->admin)
            ->get(route('procedures.index', ['search' => '730']));

        $data = $response->viewData('page')['props']['procedures'];
        $this->assertCount(2, $data);
        foreach ($data as $p) {
            $this->assertStringContainsString('730', $p['name']);
        }
    }

    public function test_practice_types_index_sort_backend(): void
    {
        PracticeType::factory()->create(['name' => 'ISEE', 'duration_minutes' => 30]);
        PracticeType::factory()->create(['name' => '730', 'duration_minutes' => 60]);
        PracticeType::factory()->create(['name' => 'RED', 'duration_minutes' => 45]);

        $response = $this->actingAs($this->admin)
            ->get(route('practice-types.index', ['sort' => 'duration_minutes', 'direction' => 'desc']))
            ->assertInertia(fn ($page) => $page
                ->where('filters.sort', 'duration_minutes')
                ->where('filters.direction', 'desc')
            );

        $durations = array_map(fn ($t) => $t['duration_minutes'], $response->viewData('page')['props']['types']);
        $sorted = $durations;
        rsort($sorted);
        $this->assertSame($sorted, $durations);
    }

    public function test_unknown_sort_column_falls_back_to_default(): void
    {
        $this->actingAs($this->admin)
            ->get(route('branches.index', ['sort' => 'malicious_column', 'direction' => 'desc']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.sort', 'name')
            );
    }
}
