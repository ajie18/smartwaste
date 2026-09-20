<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the Groq Chat Completions API. Used by
 * Admin\AiInsightController for two things: turning raw analytics numbers
 * into a written summary, and suggesting an urgency/triage note for a
 * resident report.
 *
 * Requires GROQ_API_KEY (see config/services.php -> 'groq').
 * Degrades gracefully (returns a clear "not configured" message rather
 * than throwing) when the key is unset, same pattern as SmsService.
 */
class AiInsightService
{
    private const API_URL = 'https://api.groq.com/openai/v1/chat/completions';

    private function apiKey(): ?string
    {
        return config('services.groq.api_key');
    }

    private function model(): string
    {
        return config('services.groq.model', 'llama-3.3-70b-versatile');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey());
    }

    /**
     * Turn a payload of report/pickup/fleet stats into a short written
     * analysis: trends, anomalies, and 2-3 concrete recommendations.
     */
    public function summarizeAnalytics(array $data): string
    {
        if (!$this->isConfigured()) {
            return "AI insights aren't configured yet — set GROQ_API_KEY in your .env to enable this panel.";
        }

        $prompt = <<<PROMPT
        You are an operations analyst for a municipal waste-management department (SmartWaste, Jose Dalman). Below is a JSON snapshot of the current reporting/pickup/fleet data.

        Write a short, plain-English analysis for a non-technical admin reading it on a dashboard:
        - 2-3 sentences on the most notable trend or pattern (e.g. report volume change, a report type spiking, completion-rate issues).
        - Flag anything that looks like it needs attention (e.g. a high cancellation rate, an unusually common report type suggesting a specific problem area).
        - End with 2-3 short, concrete, actionable recommendations as a bullet list.

        Keep the whole thing under 180 words. No preamble, no "Based on the data provided" — just the analysis. Plain text, not markdown headers.

        Data:
        {$this->toJson($data)}
        PROMPT;

        return $this->complete($prompt, 500);
    }

    /**
     * Suggest an urgency level and a short admin-facing triage note for a
     * single resident report.
     *
     * @return array{urgency: string, note: string}
     */
    public function triageReport(string $type, string $description): array
    {
        if (!$this->isConfigured()) {
            return [
                'urgency' => 'unknown',
                'note'    => "AI triage isn't configured — set GROQ_API_KEY in your .env.",
            ];
        }

        $prompt = <<<PROMPT
        A resident submitted this waste-collection report to a municipal admin:

        Type: {$type}
        Description: {$description}

        Respond with ONLY a JSON object (no markdown fences, no other text) with exactly these two keys:
        - "urgency": one of "low", "medium", "high"
        - "note": a one-sentence suggested action for the admin, under 25 words

        Consider illegal dumping and safety hazards as higher urgency than a single missed pickup.
        PROMPT;

        $raw = $this->complete($prompt, 200);

        $decoded = json_decode(trim($raw), true);
        if (is_array($decoded) && isset($decoded['urgency'], $decoded['note'])) {
            return $decoded;
        }

        // Model didn't return clean JSON — fail soft rather than 500.
        Log::warning('AI triage returned non-JSON response: ' . $raw);
        return ['urgency' => 'unknown', 'note' => 'Could not generate a suggestion — please review manually.'];
    }

    private function complete(string $prompt, int $maxTokens): string
    {
        try {
            $response = Http::withToken($this->apiKey())
                ->acceptJson()
                ->timeout(30)
                ->post(self::API_URL, [
                    'model'      => $this->model(),
                    'max_tokens' => $maxTokens,
                    'messages'   => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('Groq API error: ' . $response->body());
                return 'AI request failed — check the application log for details.';
            }

            return (string) $response->json('choices.0.message.content', '');
        } catch (\Throwable $e) {
            Log::error('Groq API exception: ' . $e->getMessage());
            return 'AI request failed — check the application log for details.';
        }
    }

    private function toJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
}
