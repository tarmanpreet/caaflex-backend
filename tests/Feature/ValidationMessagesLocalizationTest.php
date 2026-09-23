<?php

namespace Tests\Feature;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationMessagesLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_required_message_is_translated_italian_with_friendly_attribute(): void
    {
        config(['app.locale' => 'it', 'app.fallback_locale' => 'en']);

        $validator = Validator::make([], ['first_name' => 'required']);
        $this->assertTrue($validator->fails());

        $message = $validator->errors()->first('first_name');

        // Must be a human-readable Italian sentence, not a raw i18n key.
        $this->assertStringNotContainsString('validation.', $message);
        $this->assertSame('Il campo nome è obbligatorio.', $message);
    }

    public function test_unknown_locale_falls_back_to_english_never_raw_key(): void
    {
        config(['app.locale' => 'zz', 'app.fallback_locale' => 'en']);

        $validator = Validator::make([], ['first_name' => 'required']);
        $this->assertTrue($validator->fails());

        $message = $validator->errors()->first('first_name');

        $this->assertStringNotContainsString('validation.', $message);
        $this->assertStringNotContainsString('zz', $message);
        $this->assertSame('The first name field is required.', $message);
    }

    public function test_web_validation_error_returns_translated_message(): void
    {
        config(['app.locale' => 'it', 'app.fallback_locale' => 'en']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $client = ClientProfile::factory()->create();

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)
                ->put("/clients/{$client->id}", ['first_name' => null, 'last_name' => null]);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $messages = collect($e->errors())->flatten();

            // No message may be a raw translation key.
            foreach ($messages as $message) {
                $this->assertStringNotContainsString('validation.', $message);
            }

            $this->assertSame(
                'Il campo nome è obbligatorio.',
                $e->errors()['first_name'][0]
            );
        }
    }
}
