<?php

namespace Tests\Feature;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ClientRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_valid_payload_passes_all_rules(): void
    {
        $data = [
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'phone'          => '+1234567890',
            'date_of_birth'  => '1990-01-15',
            'fiscal_code'    => 'ABCD1234EFGH5678',
            'email'          => 'john@example.com',
            'address'        => '123 Main St',
            'city'           => 'New York',
            'province'       => 'NY',
            'postal_code'    => '10001',
            'notes'          => 'Test client',
            'create_account' => false,
            'account_email'  => null,
        ];

        $validator = Validator::make($data, (new \App\Http\Requests\StoreClientRequest())->rules());
        $this->assertFalse($validator->fails(), $validator->errors()->toJson());
    }

    public function test_missing_required_fields_returns_errors(): void
    {
        $data = [
            'first_name'    => null,
            'last_name'     => null,
            'phone'         => null,
            'date_of_birth' => null,
        ];

        $validator = Validator::make($data, (new \App\Http\Requests\StoreClientRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('first_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('last_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
        $this->assertArrayHasKey('date_of_birth', $validator->errors()->toArray());
    }

    public function test_future_date_of_birth_fails(): void
    {
        $tomorrow = now()->addDay()->format('Y-m-d');
        
        $data = [
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'phone'          => '+1234567890',
            'date_of_birth'  => $tomorrow,
            'create_account' => false,
        ];

        $validator = Validator::make($data, (new \App\Http\Requests\StoreClientRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date_of_birth', $validator->errors()->toArray());
    }

    public function test_duplicate_fiscal_code_fails(): void
    {
        $existingProfile = ClientProfile::factory()->create([
            'fiscal_code' => 'ABCD1234EFGH5678',
        ]);

        $data = [
            'first_name'     => 'Jane',
            'last_name'      => 'Smith',
            'phone'          => '+9876543210',
            'date_of_birth'  => '1995-05-20',
            'fiscal_code'    => 'ABCD1234EFGH5678',
            'create_account' => false,
        ];

        $validator = Validator::make($data, (new \App\Http\Requests\StoreClientRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('fiscal_code', $validator->errors()->toArray());
    }

    public function test_create_account_true_requires_account_email(): void
    {
        $data = [
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'phone'          => '+1234567890',
            'date_of_birth'  => '1990-01-15',
            'create_account' => true,
            'account_email'  => null,
        ];

        $validator = Validator::make($data, (new \App\Http\Requests\StoreClientRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('account_email', $validator->errors()->toArray());
    }

    public function test_create_account_true_with_valid_email_passes(): void
    {
        $data = [
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'phone'          => '+1234567890',
            'date_of_birth'  => '1990-01-15',
            'create_account' => true,
            'account_email'  => 'john.account@example.com',
        ];

        $validator = Validator::make($data, (new \App\Http\Requests\StoreClientRequest())->rules());
        $this->assertFalse($validator->fails(), $validator->errors()->toJson());
    }

    public function test_create_account_false_account_email_optional(): void
    {
        $data = [
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'phone'          => '+1234567890',
            'date_of_birth'  => '1990-01-15',
            'create_account' => false,
            'account_email'  => null,
        ];

        $validator = Validator::make($data, (new \App\Http\Requests\StoreClientRequest())->rules());
        $this->assertFalse($validator->fails(), $validator->errors()->toJson());
    }

    public function test_update_request_ignores_current_fiscal_code(): void
    {
        $client = ClientProfile::factory()->create([
            'fiscal_code' => 'ABCD1234EFGH5678',
        ]);

        $data = [
            'first_name'     => $client->first_name,
            'last_name'      => $client->last_name,
            'phone'          => $client->phone,
            'date_of_birth'  => $client->date_of_birth->format('Y-m-d'),
            'fiscal_code'    => 'ABCD1234EFGH5678',
            'create_account' => false,
        ];

        $updateRequest = new \App\Http\Requests\UpdateClientRequest();
        
        $updateRequest->setRouteResolver(function () use ($client) {
            $routes = $this->createMock(\Illuminate\Routing\RouteCollection::class);
            $route = $this->createMock(\Illuminate\Routing\Route::class);
            $route->method('parameter')->with('client')->willReturn($client);
            return $route;
        });

        $validator = Validator::make($data, $updateRequest->rules());
        $this->assertFalse($validator->fails(), $validator->errors()->toJson());
    }

    public function test_update_request_rejects_duplicate_fiscal_code(): void
    {
        $client1 = ClientProfile::factory()->create([
            'fiscal_code' => 'ABCD1234EFGH5678',
        ]);
        
        $client2 = ClientProfile::factory()->create([
            'fiscal_code' => 'EFGH5678IJKL9012',
        ]);

        $data = [
            'first_name'     => $client2->first_name,
            'last_name'      => $client2->last_name,
            'phone'          => $client2->phone,
            'date_of_birth'  => $client2->date_of_birth->format('Y-m-d'),
            'fiscal_code'    => 'ABCD1234EFGH5678',
            'create_account' => false,
        ];

        $updateRequest = new \App\Http\Requests\UpdateClientRequest();
        
        $updateRequest->setRouteResolver(function () use ($client2) {
            $routes = $this->createMock(\Illuminate\Routing\RouteCollection::class);
            $route = $this->createMock(\Illuminate\Routing\Route::class);
            $route->method('parameter')->with('client')->willReturn($client2);
            return $route;
        });

        $validator = Validator::make($data, $updateRequest->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('fiscal_code', $validator->errors()->toArray());
    }

    public function test_account_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $data = [
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'phone'          => '+1234567890',
            'date_of_birth'  => '1990-01-15',
            'create_account' => true,
            'account_email'  => 'existing@example.com',
        ];

        $validator = Validator::make($data, (new \App\Http\Requests\StoreClientRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('account_email', $validator->errors()->toArray());
    }

    public function test_phone_max_length(): void
    {
        $data = [
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'phone'          => str_repeat('1', 21),
            'date_of_birth'  => '1990-01-15',
            'create_account' => false,
        ];

        $validator = Validator::make($data, (new \App\Http\Requests\StoreClientRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    public function test_fiscal_code_size_validation(): void
    {
        $data = [
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'phone'          => '+1234567890',
            'date_of_birth'  => '1990-01-15',
            'fiscal_code'    => 'TOO_SHORT',
            'create_account' => false,
        ];

        $validator = Validator::make($data, (new \App\Http\Requests\StoreClientRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('fiscal_code', $validator->errors()->toArray());
    }

    public function test_province_size_validation(): void
    {
        $data = [
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'phone'          => '+1234567890',
            'date_of_birth'  => '1990-01-15',
            'province'       => 'ABC',
            'create_account' => false,
        ];

        $validator = Validator::make($data, (new \App\Http\Requests\StoreClientRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('province', $validator->errors()->toArray());
    }

    public function test_postal_code_size_validation(): void
    {
        $data = [
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'phone'          => '+1234567890',
            'date_of_birth'  => '1990-01-15',
            'postal_code'    => '123',
            'create_account' => false,
        ];

        $validator = Validator::make($data, (new \App\Http\Requests\StoreClientRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('postal_code', $validator->errors()->toArray());
    }

    public function test_email_format_validation(): void
    {
        $data = [
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'phone'          => '+1234567890',
            'date_of_birth'  => '1990-01-15',
            'email'          => 'not-an-email',
            'create_account' => false,
        ];

        $validator = Validator::make($data, (new \App\Http\Requests\StoreClientRequest())->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }
}
