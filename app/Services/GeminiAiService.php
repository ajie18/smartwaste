<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use UnexpectedValueException;

class GeminiAiService
{
    /**
     * @throws ConnectionException
     * @throws UnexpectedValueException
     */
    public function generateAnalyticsInsights(array $summary): string
    {
        $apiKey = config('services.gemini.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new InvalidArgumentException('The Gemini API key is not configured.');
        }

        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            .rawurlencode($model)
            .':generateContent';

        $prompt = implode("\n", [
            'You are an assistant for a municipal waste-management operations team.',
            'Analyze only the aggregate data below for the stated 30-day period.',
            'Return concise, practical observations and recommended actions as plain text.',
            'Do not invent causes, claim certainty from incomplete data, or include personal data.',
            'If there is not enough data for a conclusion, say so.',
            'Aggregate analytics data:',
            json_encode($summary, JSON_THROW_ON_ERROR),
        ]);

        $response = Http::acceptJson()
            ->withHeaders(['x-goog-api-key' => $apiKey])
            ->timeout((int) config('services.gemini.timeout', 20))
            ->post($url, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 600,
                ],
            ])
            ->throw();

        $parts = $response->json('candidates.0.content.parts', []);
        $text = collect($parts)
            ->pluck('text')
            ->filter(fn ($part) => is_string($part))
            ->implode("\n");

        if (trim($text) === '') {
            throw new UnexpectedValueException('Gemini returned no insight text.');
        }

        return trim($text);
    }
}
