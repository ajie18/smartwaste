<?php

namespace Tests\Feature;

use App\Models\MrfLocation;
use App\Models\Route;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_sign_in_and_view_the_operations_map(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'password',
        ]);
        Truck::create([
            'plate_number' => 'SW-100',
            'latitude' => 9.75,
            'longitude' => 118.74,
        ]);
        Route::create([
            'zone_name' => 'Zone 1',
            'waypoints_json' => ['pins' => [[9.75, 118.74]]],
        ]);
        MrfLocation::create([
            'name' => 'North MRF',
            'latitude' => 9.76,
            'longitude' => 118.75,
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.map'));

        $this->get('/admin/map')
            ->assertOk()
            ->assertSee('SmartWaste operations map')
            ->assertSee('SW-100')
            ->assertSee('Zone 1')
            ->assertSee('North MRF');
    }

    public function test_non_admin_cannot_sign_in_to_the_admin_map(): void
    {
        $user = User::factory()->create([
            'role' => 'resident',
            'password' => 'password',
        ]);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertForbidden();

        $this->assertGuest();
    }

    public function test_map_redirects_guests_to_admin_login(): void
    {
        $this->get('/admin/map')->assertRedirect(route('login'));
    }
}
