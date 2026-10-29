<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ForgotPasswordSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_the_reset_pin_by_sms(): void
    {
        config([
            'services.iprogsms.base_url' => 'https://www.iprogsms.com/api/v1',
            'services.iprogsms.api_token' => 'test-token',
            'services.iprogsms.sender_name' => null,
        ]);
        Http::fake([
            'www.iprogsms.com/*' => Http::response([
                'status' => 200,
                'message_id' => 'test-message',
            ]),
        ]);

        $user = User::factory()->create([
            'phone' => '09123456789',
        ]);

        $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ])->assertOk()
            ->assertJson([
                'message' => 'If that account exists and has a phone number on file, the reset request was processed.',
            ]);

        $this->assertNotNull($user->fresh()->reset_pin);
        Http::assertSent(fn (Request $request) => $request->data()['phone_number'] === '09123456789'
            && str_contains($request->data()['message'], 'password reset PIN'));
    }
}
