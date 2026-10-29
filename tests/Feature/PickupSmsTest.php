<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\RouteAssignment;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PickupSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_send_a_pickup_reminder_to_the_assigned_driver(): void
    {
        config([
            'services.iprogsms.base_url' => 'https://www.iprogsms.com/api/v1',
            'services.iprogsms.api_token' => 'test-token',
            'services.iprogsms.sender_name' => null,
        ]);
        Http::fake([
            'www.iprogsms.com/*' => Http::response([
                'status' => 200,
                'message_id' => 'pickup-message',
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $driver = User::factory()->create([
            'role' => 'driver',
            'phone' => '09123456789',
        ]);
        $truck = Truck::create(['plate_number' => 'SW-200']);
        $route = Route::create(['zone_name' => 'Zone 2']);
        RouteAssignment::create([
            'driver_id' => $driver->id,
            'truck_id' => $truck->id,
            'route_id' => $route->id,
            'assigned_date' => now()->timezone('Asia/Manila')->toDateString(),
            'status' => 'pending',
        ]);
        $token = $admin->createToken('test-admin')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/admin/notify-pickup-today')
            ->assertOk()
            ->assertJson([
                'message' => 'Pickup reminder sent to the assigned driver',
                'message_id' => 'pickup-message',
                'sent' => 1,
            ]);

        Http::assertSent(fn (Request $request) => $request->data()['phone_number'] === '09123456789'
            && str_contains($request->data()['message'], 'Zone 2'));
    }
}
