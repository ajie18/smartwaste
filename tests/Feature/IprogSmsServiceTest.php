<?php

namespace Tests\Feature;

use App\Services\IprogSmsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class IprogSmsServiceTest extends TestCase
{
    public function test_it_sends_the_sms_using_the_configured_iprogsms_form_fields(): void
    {
        config([
            'services.iprogsms.base_url' => 'https://www.iprogsms.com/api/v1',
            'services.iprogsms.api_token' => 'test-token',
            'services.iprogsms.sender_name' => 'SmartWaste',
            'services.iprogsms.timeout' => 10,
        ]);
        Http::fake([
            'www.iprogsms.com/*' => Http::response([
                'status' => 200,
                'message_id' => 'test-message',
            ]),
        ]);

        $result = app(IprogSmsService::class)->send('09123456789', 'Test message');

        $this->assertSame('test-message', $result['message_id']);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://www.iprogsms.com/api/v1/sms_messages'
            && $request->data()['api_token'] === 'test-token'
            && $request->data()['phone_number'] === '09123456789'
            && $request->data()['message'] === 'Test message'
            && $request->data()['sender_name'] === 'SmartWaste');
    }

    public function test_it_reports_missing_api_credentials(): void
    {
        config(['services.iprogsms.api_token' => null]);

        $this->expectException(RuntimeException::class);
        app(IprogSmsService::class)->send('09123456789', 'Test message');
    }
}
