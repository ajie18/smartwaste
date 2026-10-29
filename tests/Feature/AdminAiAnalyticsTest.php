<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminAiAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_request_gemini_analytics_insights(): void
    {
        config([
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-test-model',
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Review pending report volume.']]]],
                ],
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-admin')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/admin/analytics/insights')
            ->assertOk()
            ->assertJsonPath('insights', 'Review pending report volume.')
            ->assertJsonPath('model', 'gemini-test-model')
            ->assertJsonStructure(['period' => ['start', 'end']]);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-goog-api-key', 'test-gemini-key'));
    }

    public function test_guest_cannot_request_analytics_insights(): void
    {
        $this->postJson('/api/admin/analytics/insights')->assertUnauthorized();
    }
}
