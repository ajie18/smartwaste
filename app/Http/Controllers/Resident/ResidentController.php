<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Events\RealtimeEvent;
use App\Models\AppSetting;
use App\Models\Announcement;
use App\Models\Report;
use App\Models\RouteAssignment;
use App\Models\TripLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ResidentController extends Controller
{
    /**
     * A truck is only "really" trackable once the driver has pressed Start
     * Route (status === 'in_progress') AND has a GPS ping within the last
     * few minutes.
     */
    private const LIVE_LOCATION_FRESHNESS_SECONDS = 3 * 60;

    /**
     * Both the driver and resident sides must agree on what "today" means.
     * The driver dashboard computes it in Asia/Manila local time — we
     * standardize on the same date string here to avoid the DB server's
     * own timezone silently disagreeing around midnight.
     */
    private function manilaToday(): string
    {
        return Carbon::now('Asia/Manila')->toDateString();
    }

    public function getTodayTruck(Request $request)
    {
        $today = $this->manilaToday();

        $assignments = RouteAssignment::query()
            ->join('trucks', 'route_assignments.truck_id', '=', 'trucks.id')
            ->join('routes', 'route_assignments.route_id', '=', 'routes.id')
            ->join('users as drivers', 'route_assignments.driver_id', '=', 'drivers.id')
            ->where('route_assignments.assigned_date', $today)
            ->select(
                'route_assignments.*',
                'drivers.name as driver_name',
                'trucks.plate_number',
                'trucks.model',
                'trucks.latitude as truck_lat',
                'trucks.longitude as truck_lng',
                'trucks.location_updated_at as truck_loc_updated_at',
                'routes.id as route_id',
                'routes.zone_name',
                'routes.waypoints_json'
            )
            ->get();

        if ($assignments->isEmpty()) {
            return response()->json(null);
        }

        $resident = $request->user();
        $residentLat = $resident->latitude;
        $residentLng = $resident->longitude;
        $statusPriority = ['in_progress' => 0, 'pending' => 1, 'completed' => 2, 'cancelled' => 3];

        $assignment = $request->integer('assignment_id')
            ? $assignments->firstWhere('id', $request->integer('assignment_id'))
            : null;

        if (!$assignment) {
            $assignment = $assignments->sortBy(function ($candidate) use ($residentLat, $residentLng, $statusPriority) {
            $distance = PHP_INT_MAX;
            if ($residentLat !== null && $residentLng !== null) {
                $waypoints = $this->parseWaypoints($candidate->waypoints_json);
                $points = array_merge($waypoints['road'] ?? [], $waypoints['pins'] ?? [], $waypoints['mrfs'] ?? []);
                foreach ($points as $point) {
                    if (!isset($point['lat'], $point['lng'])) continue;
                    $distance = min($distance, $this->distanceKm($residentLat, $residentLng, $point['lat'], $point['lng']));
                }
            }

            return [
                $statusPriority[$candidate->status] ?? 9,
                $distance,
                $candidate->id,
            ];
            })->first();
        }

        $route = collect($assignment)->toArray();
        if (is_string($route['progress_log'] ?? null)) {
            $route['progress_log'] = json_decode($route['progress_log'], true);
        }
        $route['parsed_waypoints'] = $this->parseWaypoints($assignment->waypoints_json);
        $route['route_started'] = $assignment->status === 'in_progress';
        $route['route_completed'] = $assignment->status === 'completed';
        $route['route_cancelled'] = $assignment->status === 'cancelled';
        $route['available_assignments'] = $assignments->map(fn ($item) => [
            'id' => $item->id,
            'driver_id' => $item->driver_id,
            'driver_name' => $item->driver_name,
            'zone_name' => $item->zone_name,
            'status' => $item->status,
        ])->values();
        $route['has_live_location'] = false;
        $route['latitude'] = null;
        $route['longitude'] = null;

        if ($route['route_started'] && $assignment->truck_lat !== null && $assignment->truck_lng !== null && $assignment->truck_loc_updated_at) {
            $secondsSinceUpdate = now()->diffInSeconds(Carbon::parse($assignment->truck_loc_updated_at));
            if ($secondsSinceUpdate < self::LIVE_LOCATION_FRESHNESS_SECONDS) {
                $route['has_live_location'] = true;
                $route['latitude'] = $assignment->truck_lat;
                $route['longitude'] = $assignment->truck_lng;
            }
        }

        unset($route['truck_lat'], $route['truck_lng'], $route['truck_loc_updated_at']);

        return response()->json($route);
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return 6371 * 2 * asin(min(1, sqrt($a)));
    }

    public function getRouteWaypoints(Request $request)
    {
        $routeId = $request->query('route_id');
        if (!$routeId) {
            return response()->json(['error' => 'route_id required'], 400);
        }

        $route = \App\Models\Route::select('id', 'zone_name', 'description', 'waypoints_json')->find($routeId);

        if (!$route) {
            return response()->json(['error' => 'Route not found'], 404);
        }

        $parsed = null;
        if ($route->waypoints_json) {
            $decoded = is_string($route->waypoints_json) ? json_decode($route->waypoints_json, true) : $route->waypoints_json;
            $parsed = $decoded;
        }

        return response()->json([
            'waypoints_json' => $route->waypoints_json,
            'raw_waypoints'  => $parsed,
            'zone_name'      => $route->zone_name,
            'description'    => $route->description,
            'has_waypoints'  => !is_null($parsed),
        ]);
    }

    public function getPickupSchedule()
    {
        $schedule = RouteAssignment::query()
            ->join('trucks', 'route_assignments.truck_id', '=', 'trucks.id')
            ->join('routes', 'route_assignments.route_id', '=', 'routes.id')
            ->orderByDesc('route_assignments.assigned_date')
            ->get([
                'route_assignments.assigned_date',
                'trucks.plate_number',
                'routes.zone_name',
                'route_assignments.status',
                'route_assignments.pickup_type',
            ]);

        return response()->json($schedule);
    }

    public function getTruckLocation(Request $request, $truckId)
    {
        $today = $this->manilaToday();

        $assignment = RouteAssignment::where('truck_id', $truckId)
            ->where('assigned_date', $today)
            ->first();

        if (!$assignment || !in_array($assignment->status, ['in_progress', 'completed'], true)) {
            return response()->json([]);
        }

        // Real-time pings land in trip_logs (written by the driver's
        // location updates), so pull the recent trail from there for
        // smooth resident-side animation.
        $logs = TripLog::where('assignment_id', $assignment->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('logged_at')
            ->limit(500)
            ->get(['latitude', 'longitude', 'logged_at as recorded_at']);

        if ($assignment->status === 'completed') {
            return response()->json($logs->sortBy('recorded_at')->values());
        }

        $fresh = $logs->filter(function ($log) {
            return now()->diffInSeconds(Carbon::parse($log->recorded_at)) < self::LIVE_LOCATION_FRESHNESS_SECONDS;
        })->values();

        if ($fresh->isNotEmpty()) {
            return response()->json($fresh);
        }

        // Fallback: the trucks table's own snapshot covers the brief race
        // right after "Start Route" before the first trip_logs row lands.
        $truck = \App\Models\Truck::find($truckId);
        if (
            $truck && $truck->latitude !== null && $truck->longitude !== null && $truck->location_updated_at
            && now()->diffInSeconds($truck->location_updated_at) < self::LIVE_LOCATION_FRESHNESS_SECONDS
        ) {
            return response()->json([[
                'latitude'     => $truck->latitude,
                'longitude'    => $truck->longitude,
                'recorded_at'  => $truck->location_updated_at,
            ]]);
        }

        return response()->json([]);
    }

    public function createReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'        => 'required|string',
            'description' => 'required|string',
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
            'image_url'   => 'required|string',
        ], [
            'type.required'        => 'Type required',
            'description.required' => 'Description required',
            'latitude.required'    => 'Location required',
            'longitude.required'   => 'Location required',
            'image_url.required'   => 'Image required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $report = Report::create([
            'resident_id' => $request->user()->id,
            'type'        => $request->type,
            'description' => $request->description,
            'latitude'    => $request->latitude,
            'longitude'   => $request->longitude,
            'image_url'   => $request->image_url,
        ]);

        // Live-pushes the new report onto every connected driver's open-
        // issues map (driver-dashboard.js's attachNewReportListener /
        // echo-bridge.js), same as the old socket.io 'new-resident-report'.
        broadcast(new RealtimeEvent('reports', 'new-resident-report', [
            'id'            => $report->id,
            'type'          => $report->type,
            'description'   => $report->description,
            'latitude'      => $report->latitude,
            'longitude'     => $report->longitude,
            'image_url'     => $report->image_url,
            'status'        => $report->status,
            'created_at'    => $report->created_at,
            'user_id'       => $request->user()->id,
            'resident_name' => $request->user()->name,
        ]));

        return response()->json(['message' => 'Report submitted successfully']);
    }

    public function getReports(Request $request)
    {
        $reports = Report::where('resident_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($reports);
    }

    public function getNotifications(Request $request)
    {
        $notes = \App\Models\AppNotification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($notes);
    }

    public function getAnnouncements()
    {
        return response()->json(Announcement::orderByDesc('created_at')->get());
    }

    public function shareMyLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'latitude and longitude required'], 400);
        }

        $request->user()->forceFill([
            'latitude'             => $request->latitude,
            'longitude'            => $request->longitude,
            'location_updated_at'  => now(),
        ])->save();

        return response()->json([
            'message'   => 'Location shared',
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude,
        ]);
    }

    public function getResidentLocations()
    {
        $residents = User::where('role', 'resident')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('location_updated_at', '>', now()->subHours(2))
            ->orderByDesc('location_updated_at')
            ->get(['id', 'name', 'latitude', 'longitude', 'location_updated_at']);

        return response()->json($residents);
    }

    public function getMRFLocation()
    {
        $setting = AppSetting::find('mrf_location');

        if ($setting && $setting->value) {
            return response()->json(json_decode($setting->value, true));
        }

        // No hardcoded fallback — if the MRF hasn't been configured, tell
        // the frontend there simply isn't one to show.
        return response()->json(null);
    }

    // -----------------------------------------------------------------
    // Profile / account management
    // -----------------------------------------------------------------

    public function getProfile(Request $request)
    {
        $user = User::select('id', 'name', 'email', 'phone', 'role')->find($request->user()->id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    public function updateProfile(Request $request)
    {
        $name = trim((string) $request->name);
        if (!$name) {
            return response()->json(['error' => 'Name is required'], 400);
        }

        $request->user()->forceFill([
            'name'  => $name,
            'phone' => $request->phone ? trim($request->phone) : null,
        ])->save();

        return response()->json(['message' => 'Profile updated']);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'current_password and new_password are required'], 400);
        }

        $user = $request->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 401);
        }

        $user->forceFill([
            'password' => \Illuminate\Support\Facades\Hash::make($request->new_password),
        ])->save();

        return response()->json(['message' => 'Password changed successfully']);
    }

    /**
     * Port of parseWaypoints() from residentController.js / driverController.js.
     */
    private function parseWaypoints($raw): array
    {
        if (!$raw) {
            return ['road' => [], 'pins' => [], 'mrfs' => []];
        }

        $data = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($data)) {
            return ['road' => [], 'pins' => [], 'mrfs' => []];
        }

        if (array_is_list($data)) {
            $pins = [];
            foreach ($data as $i => $wp) {
                $pins[] = [
                    'lat'  => (float) ($wp['lat'] ?? 0),
                    'lng'  => (float) ($wp['lng'] ?? 0),
                    'name' => $wp['name'] ?? ('Stop ' . ($i + 1)),
                    'num'  => $wp['stopNumber'] ?? ($i + 1),
                ];
            }
            return ['road' => [], 'pins' => $pins, 'mrfs' => []];
        }

        $road = array_map(fn ($v) => [
            'lat' => (float) ($v['lat'] ?? 0),
            'lng' => (float) ($v['lng'] ?? 0),
        ], $data['road_path'] ?? $data['road'] ?? []);

        $pins = [];
        foreach (($data['pin_stops'] ?? $data['pins'] ?? []) as $i => $p) {
            $pins[] = [
                'lat'  => (float) ($p['lat'] ?? 0),
                'lng'  => (float) ($p['lng'] ?? 0),
                'name' => $p['name'] ?? ('Stop ' . ($i + 1)),
                'num'  => $p['stopNumber'] ?? ($i + 1),
            ];
        }

        $mrfs = array_map(fn ($m) => [
            'lat'  => (float) ($m['lat'] ?? 0),
            'lng'  => (float) ($m['lng'] ?? 0),
            'name' => $m['name'] ?? 'MRF Stop',
        ], $data['mrf_stops'] ?? $data['mrf'] ?? []);

        return ['road' => $road, 'pins' => $pins, 'mrfs' => $mrfs];
    }
}
