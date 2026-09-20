<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Laravel port of utils/sms.js — IPROG SMS API.
 * Docs: https://sms.iprogtech.com/api/v1/documentation
 * Env: IPROG_API_TOKEN (see config/services.php -> 'sms.api_key')
 */
class SmsService
{
    private const BASE_URL = 'https://sms.iprogtech.com/api/v1';

    /**
     * Port of normalizePhone() from sms.js — accepts +63/63/9-prefixed
     * numbers and normalizes everything to the local 09XXXXXXXXX format
     * IPROG expects. Returns null if it's not a valid PH mobile number.
     */
    public function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $p = preg_replace('/[\s\-()]/', '', $phone);

        if (str_starts_with($p, '+63')) {
            $p = '0' . substr($p, 3);
        }
        if (str_starts_with($p, '63') && strlen($p) === 12) {
            $p = '0' . substr($p, 2);
        }
        if (preg_match('/^9\d{9}$/', $p)) {
            $p = '0' . $p;
        }

        if (!preg_match('/^09\d{9}$/', $p)) {
            return null;
        }

        return $p;
    }

    public function sendSMS(string $phone, string $message): array
    {
        $apiToken = config('services.sms.api_key');

        if (!$apiToken) {
            Log::warning("IPROG_API_TOKEN is not set — would have sent SMS to {$phone}: {$message}");
            return ['ok' => true, 'simulated' => true];
        }

        $phoneNumber = $this->normalizePhone($phone);
        if (!$phoneNumber) {
            return ['ok' => false, 'error' => 'Invalid Philippine phone number'];
        }

        try {
            $response = Http::post(self::BASE_URL . '/sms_messages', [
                'api_token'    => $apiToken,
                'phone_number' => $phoneNumber,
                'message'      => substr($message, 0, 300),
            ]);

            $data = $response->json() ?? [];

            $ok = $response->successful()
                && (($data['status'] ?? null) === 200
                    || ($data['status'] ?? null) === 'success'
                    || !empty($data['message_id']));

            if ($ok) {
                Log::info("SMS sent to {$phoneNumber} — id: " . ($data['message_id'] ?? 'ok'));
                return ['ok' => true, 'data' => $data];
            }

            Log::error('IPROG SMS error: ' . json_encode($data));
            return ['ok' => false, 'error' => $data['message'] ?? $data['error'] ?? 'SMS send failed', 'data' => $data];
        } catch (\Throwable $e) {
            Log::error('IPROG SMS network error: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendOTP(string $phone, string $code, string $purpose = 'verification'): array
    {
        $message = $purpose === 'reset'
            ? "SmartWaste: Your password reset code is {$code}. Valid for 15 minutes. Do not share this code."
            : "SmartWaste: Your verification code is {$code}. Valid for 24 hours. Do not share this code.";

        return $this->sendSMS($phone, $message);
    }

    public function sendPickupNotice(string $phone, array $options = []): array
    {
        $zone = !empty($options['zoneName']) ? " ({$options['zoneName']})" : '';
        $truck = !empty($options['plateNumber']) ? " Truck: {$options['plateNumber']}." : '';
        $type = (!empty($options['pickupType']) && $options['pickupType'] !== 'regular')
            ? " Type: {$options['pickupType']}."
            : '';

        $message = "SmartWaste: Trash collection is scheduled TODAY{$zone}.{$truck}{$type} Please put out your garbage. - MENRO Jose Dalman";

        return $this->sendSMS($phone, $message);
    }
}
