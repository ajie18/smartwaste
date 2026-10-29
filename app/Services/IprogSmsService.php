<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class IprogSmsService
{
    /**
     * @throws ConnectionException
     * @throws RuntimeException
     */
    public function send(string $phoneNumber, string $message): array
    {
        $apiToken = config('services.iprogsms.api_token');

        if (! is_string($apiToken) || $apiToken === '') {
            throw new RuntimeException('The iProgSMS API token is not configured.');
        }

        $baseUrl = rtrim((string) config('services.iprogsms.base_url'), '/');
        $payload = [
            'api_token' => $apiToken,
            'phone_number' => $phoneNumber,
            'message' => $message,
        ];

        $senderName = config('services.iprogsms.sender_name');
        if (is_string($senderName) && $senderName !== '') {
            $payload['sender_name'] = $senderName;
        }

        $response = Http::asForm()
            ->timeout((int) config('services.iprogsms.timeout', 10))
            ->post($baseUrl.'/sms_messages', $payload)
            ->throw();

        $result = $response->json();
        if (! is_array($result) || (int) ($result['status'] ?? 0) !== 200) {
            throw new RuntimeException('iProgSMS did not accept the SMS request.');
        }

        return $result;
    }
}
