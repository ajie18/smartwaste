<?php

namespace Tests\Feature;

use App\Services\GeminiAiService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class GeminiAiServiceTest extends TestCase
{
    public function test_it_sends_aggregate_analytics_to_gemini_and_returns_generated_text(): void
    {
        config([
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-test-model',
            'services.gemini.timeout' => 20,
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Reports are trending upward.'],
                                ['text' => 'Review pickup capacity.'],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $summary = [
            'reports' => ['total' => 7, 'by_status' => ['pending' => 4]],
            'pickups' => ['total' => 12],
            'fleet' => ['total_trucks' => 5],
        ];

        $result = app(GeminiAiService::class)->generateAnalyticsInsights($summary);

        $this->assertSame("Reports are trending upward.\nReview pickup capacity.", $result);
        Http::assertSent(function (Request $request): bool {
            $prompt = $request->data()['contents'][0]['parts'][0]['text'];

            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model:generateContent'
                && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
                && str_contains($prompt, '"total":7')
                && ! str_contains($prompt, 'resident email');
        });
    }

    public function test_it_fails_explicitly_when_the_api_key_is_missing(): void
    {
        config(['services.gemini.api_key' => null]);

        $this->expectException(InvalidArgumentException::class);
        app(GeminiAiService::class)->generateAnalyticsInsights([]);
    }
}
