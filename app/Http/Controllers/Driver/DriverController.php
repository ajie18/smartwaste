<?php

namespace App\Http\Controllers\Driver;

use App\Events\RealtimeEvent;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Announcement;
use App\Models\Report;
use App\Models\Route;
use App\Models\RouteAssignment;
use App\Models\TripLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    private function manilaToday(): string
    {
        return Carbon::now('Asia/Manila')->toDateString();
    }


    private function autoCancelMissedAssignments(): void
    {
        try {
            $today = $this->manilaToday();
            $affected = RouteAssignment::where('assigned_date', '<', $today)
                ->whereIn('status', ['pending', 'in_progress'])
                ->update(['status' => 'cancelled']);

            if ($affected) {
                logger()->info("Auto-cancelled {$affected} missed/never-started assignment(s) before {$today}");
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function getTodayAssignment(Request $request)
    {
        $this->autoCancelMissedAssignments();
        $today = $this->manilaToday();

        $assignments = RouteAssignment::query()
            ->join('routes', 'route_assignments.route_id', '=', 'routes.id')
            ->join('trucks', 'route_assignments.truck_id', '=', 'trucks.id')
            ->where('route_assignments.driver_id', $request->user()->id)
            ->where('route_assignments.assigned_date', $today)
            ->orderBy('route_assignments.id')
            ->get([
                'route_assignments.id',
                'route_assignments.assigned_date',
                'route_assignments.status',
                'route_assignments.pickup_type',
                'route_assignments.progress_log',
                'routes.id as route_id',
                'routes.zone_name',
                'routes.description as zone_description',
                'trucks.id as truck_id',
                'trucks.plate_number',
                'trucks.model',
                'trucks.latitude',
                'trucks.longitude',
            ]);

        $assignments->transform(function ($assignment) {
            if (is_string($assignment->progress_log)) {
                $assignment->progress_log = json_decode($assignment->progress_log, true);
            }

            return $assignment;
        });

        return response()->json($assignments);
    }

    public function updateAssignmentStatus(Request $request, $id)
    {
        $status = $request->input('status');
        $allowed = ['pending', 'in_progress', 'completed', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return response()->json(['error' => 'Invalid status value'], 400);
        }

        $assignment = RouteAssignment::where('id', $id)->where('driver_id', $request->user()->id)->first();
        if (!$assignment) {
            return response()->json(['error' => 'Not authorised'], 403);
        }

        $progressLog = $request->input('progress_log');
        $updates = ['status' => $status];
        if (is_array($progressLog)) {
            $updates['progress_log'] = $progressLog + ['completed_at' => now()->toIso8601String()];
        }
        $assignment->update($updates);

        if ($status === 'completed') {
            TripLog::create([
                'assignment_id' => $id,
                'completed_at'  => now(),
                'status'        => 'completed',
            ]);
        }

        broadcast(new RealtimeEvent("driver.{$request->user()->id}", 'assignment-status-changed', [
            'assignmentId' => (int) $id,
            'status'       => $status,
            'timestamp'    => now()->toIso8601String(),
        ]));

        broadcast(new RealtimeEvent('reports', 'assignment-public-status', [
            'assignmentId' => (int) $id,
            'status'       => $status,
            'driverId'     => $request->user()->id,
            'timestamp'    => now()->toIso8601String(),
        ]));

        return response()->json(['message' => "Status updated to {$status}"]);
    }

    public function getMySchedule(Request $request)
    {
        $this->autoCancelMissedAssignments();

        $rows = RouteAssignment::query()
            ->join('routes', 'route_assignments.route_id', '=', 'routes.id')
            ->join('trucks', 'route_assignments.truck_id', '=', 'trucks.id')
            ->where('route_assignments.driver_id', $request->user()->id)
            ->orderByDesc('route_assignments.assigned_date')
            ->limit(30)
            ->get([
                'route_assignments.id',
                'route_assignments.assigned_date',
                'route_assignments.status',
                'route_assignments.pickup_type',
                'routes.zone_name',
                'trucks.plate_number',
                'trucks.model',
            ]);

        return response()->json($rows);
    }

    public function updateMyLocation(Request $request)
    {
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $accuracy = $request->input('accuracy');
        $assignmentId = $request->input('assignment_id');

        if ($latitude === null || $longitude === null) {
            return response()->json(['error' => 'latitude and longitude required'], 400);
        }

        $today = $this->manilaToday();

        $query = RouteAssignment::where('driver_id', $request->user()->id)
            ->where('assigned_date', $today);

        if ($assignmentId) {
            $assignment = (clone $query)->where('id', $assignmentId)->first();
        } else {
            $assignment = (clone $query)
                ->orderByRaw("FIELD(status,'in_progress','pending','completed','cancelled')")
                ->orderBy('id')
                ->first();
        }

        if (!$assignment) {
            return response()->json(['error' => 'No matching assignment today'], 404);
        }

        \App\Models\Truck::where('id', $assignment->truck_id)->update([
            'latitude'             => $latitude,
            'longitude'            => $longitude,
            'location_updated_at'  => now(),
        ]);

        TripLog::create([
            'assignment_id' => $assignment->id,
            'latitude'      => $latitude,
            'longitude'     => $longitude,
            'logged_at'     => now(),
        ]);

        broadcast(new RealtimeEvent("dispatcher.{$request->user()->id}", 'driver-moved', [
            'driverId'     => $request->user()->id,
            'assignmentId' => $assignment->id,
            'latitude'     => $latitude,
            'longitude'    => $longitude,
            'accuracy'     => $accuracy ?? 0,
            'timestamp'    => now()->valueOf(),
        ]));

        broadcast(new RealtimeEvent('reports', 'truck-location', [
            'truckId'   => $assignment->truck_id,
            'latitude'  => $latitude,
            'longitude' => $longitude,
            'accuracy'  => $accuracy ?? 0,
            'timestamp' => now()->valueOf(),
        ]));

        return response()->json(['message' => 'Location updated', 'accuracy' => $accuracy]);
    }

    public function submitReport(Request $request)
    {
        $type = $request->input('type');
        $description = $request->input('description');

        if (!$type || !$description) {
            return response()->json(['error' => 'Type and description required'], 400);
        }

        Report::create([
            'resident_id'   => $request->user()->id,
            'type'          => $type,
            'description'   => $description,
            'location_note' => $request->input('location_note'),
            'status'        => 'pending',
        ]);

        broadcast(new RealtimeEvent("driver.{$request->user()->id}", 'report-submitted', [
            'success' => true,
            'message' => 'Report submitted successfully',
        ]));

        return response()->json(['message' => 'Report submitted']);
    }

    public function getMyReports(Request $request)
    {
        $rows = Report::where('resident_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'type', 'description', 'location_note', 'status', 'created_at']);

        return response()->json($rows);
    }

    public function getAnnouncements()
    {
        $rows = Announcement::with('creator:id,name')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'title'      => $a->title,
                'message'    => $a->message,
                'created_at' => $a->created_at,
                'admin_name' => $a->creator->name ?? null,
            ]);

        return response()->json($rows);
    }

    public function getTripHistory(Request $request)
    {
        $rows = TripLog::query()
            ->join('route_assignments', 'trip_logs.assignment_id', '=', 'route_assignments.id')
            ->join('routes', 'route_assignments.route_id', '=', 'routes.id')
            ->join('trucks', 'route_assignments.truck_id', '=', 'trucks.id')
            ->where('route_assignments.driver_id', $request->user()->id)
            ->orderByRaw('COALESCE(trip_logs.completed_at, trip_logs.end_time, trip_logs.logged_at) DESC')
            ->limit(20)
            ->get([
                'trip_logs.id',
                DB::raw('COALESCE(trip_logs.completed_at, trip_logs.end_time, route_assignments.assigned_date) as completed_at'),
                'route_assignments.assigned_date',
                'route_assignments.pickup_type',
                'route_assignments.status',
                'routes.zone_name',
                'trucks.plate_number',
            ]);

        return response()->json($rows);
    }

    public function getProfile(Request $request)
    {
        $user = $request->user()->only(['id', 'name', 'email', 'phone', 'address', 'role']);
        return response()->json($user);
    }

    public function updateProfile(Request $request)
    {
        $request->user()->forceFill([
            'name'    => $request->input('name'),
            'phone'   => $request->input('phone'),
            'address' => $request->input('address'),
        ])->save();

        broadcast(new RealtimeEvent("driver.{$request->user()->id}", 'profile-updated', [
            'success' => true,
            'message' => 'Profile updated successfully',
        ]));

        return response()->json(['message' => 'Profile updated']);
    }

    public function getRouteWaypoints(Request $request)
    {
        $routeId = $request->query('route_id');
        if (!$routeId) {
            return response()->json(['error' => 'route_id is required'], 400);
        }

        $route = Route::find($routeId);
        if (!$route) {
            return response()->json(['error' => 'Route not found'], 404);
        }

        if ($route->waypoints_json) {
            $parsed = is_string($route->waypoints_json) ? json_decode($route->waypoints_json, true) : $route->waypoints_json;

            return response()->json([
                'waypoints'      => [],
                'raw_waypoints'  => $parsed,
                'waypoints_json' => $route->waypoints_json,
                'zone_name'      => $route->zone_name,
                'description'    => $route->description,
                'has_waypoints'  => true,
            ]);
        }

        return response()->json([
            'waypoints'     => [],
            'raw_waypoints' => null,
            'zone_name'     => $route->zone_name,
            'description'   => $route->description,
            'has_waypoints' => false,
        ]);
    }

    public function getStats(Request $request)
    {
        $driverId = $request->user()->id;

        $stats = RouteAssignment::where('driver_id', $driverId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->first();

        return response()->json($stats ?? [
            'total' => 0,
            'completed' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'cancelled' => 0,
        ]);
    }

    public function getTodayRouteDetails(Request $request)
    {
        $this->autoCancelMissedAssignments();
        $today = $this->manilaToday();

        $assignments = RouteAssignment::query()
            ->join('routes', 'route_assignments.route_id', '=', 'routes.id')
            ->join('trucks', 'route_assignments.truck_id', '=', 'trucks.id')
            ->where('route_assignments.driver_id', $request->user()->id)
            ->where('route_assignments.assigned_date', $today)
            ->orderBy('route_assignments.id')
            ->get([
                'route_assignments.id',
                'route_assignments.assigned_date',
                'route_assignments.status',
                'route_assignments.pickup_type',
                'routes.id as route_id',
                'routes.zone_name',
                'routes.description as zone_description',
                'routes.waypoints_json',
                'trucks.id as truck_id',
                'trucks.plate_number',
                'trucks.model',
                'trucks.latitude',
                'trucks.longitude',
            ])
            ->map(function ($a) {
                $a->waypoints = $a->waypoints_json
                    ? (is_string($a->waypoints_json) ? json_decode($a->waypoints_json, true) : $a->waypoints_json)
                    : null;
                unset($a->waypoints_json);
                return $a;
            });

        return response()->json($assignments->values());
    }

    public function getLocationHistory(Request $request)
    {
        $limit = (int) $request->query('limit', 50);
        $today = $this->manilaToday();

        $rows = TripLog::query()
            ->join('route_assignments', 'trip_logs.assignment_id', '=', 'route_assignments.id')
            ->join('routes', 'route_assignments.route_id', '=', 'routes.id')
            ->where('route_assignments.driver_id', $request->user()->id)
            ->where('route_assignments.assigned_date', $today)
            ->orderByDesc('trip_logs.logged_at')
            ->limit($limit)
            ->get([
                'trip_logs.latitude',
                'trip_logs.longitude',
                'trip_logs.logged_at as timestamp',
                'routes.zone_name',
            ]);

        return response()->json($rows);
    }

    public function updateRouteProgress(Request $request)
    {
        $assignmentId = $request->input('assignment_id');
        $stopIndex = $request->input('stop_index');
        $stopStatus = $request->input('stop_status', 'reached');

        if (!$assignmentId || $stopIndex === null) {
            return response()->json(['error' => 'assignment_id and stop_index required'], 400);
        }

        $assignment = RouteAssignment::where('id', $assignmentId)
            ->where('driver_id', $request->user()->id)
            ->first();

        if (!$assignment) {
            return response()->json(['error' => 'Not authorised'], 403);
        }

        DB::statement(
            "UPDATE route_assignments SET progress_log = JSON_SET(COALESCE(progress_log, '{}'), CONCAT('$.stop_', ?), ?) WHERE id = ?",
            [$stopIndex, $stopStatus, $assignmentId]
        );

        broadcast(new RealtimeEvent("driver.{$request->user()->id}", 'stop-updated', [
            'assignmentId' => $assignmentId,
            'stopIndex'    => $stopIndex,
            'status'       => $stopStatus,
            'timestamp'    => now()->toIso8601String(),
        ]));

        return response()->json(['message' => "Stop {$stopIndex} updated to {$stopStatus}"]);
    }


    public function getConnectedDrivers()
    {
        return response()->json([]);
    }

    public function createQuickReport(Request $request)
    {
        $type = $request->input('type');
        $description = $request->input('description');

        if (!$type || !$description) {
            return response()->json(['error' => 'Type and description required'], 400);
        }

        $report = Report::create([
            'resident_id'   => $request->user()->id,
            'type'          => $type,
            'description'   => $description,
            'location_note' => $request->input('location_note'),
            'latitude'      => $request->input('latitude'),
            'longitude'     => $request->input('longitude'),
            'image_url'     => $request->input('image_url'),
            'status'        => 'pending',
        ]);

        broadcast(new RealtimeEvent("dispatcher.{$request->user()->id}", 'new-report', [
            'reportId'    => $report->id,
            'driverId'    => $request->user()->id,
            'type'        => $type,
            'description' => $description,
            'location'    => ['latitude' => $request->input('latitude'), 'longitude' => $request->input('longitude')],
            'timestamp'   => now()->toIso8601String(),
        ]));

        return response()->json([
            'message'  => 'Report submitted successfully',
            'reportId' => $report->id,
        ]);
    }

    public function getNotifications(Request $request)
    {
        $rows = AppNotification::where('user_id', $request->user()->id)
            ->where('user_type', 'driver')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'message', 'type', 'is_read', 'created_at', 'related_id']);

        return response()->json($rows);
    }

    public function markNotificationRead(Request $request, $notificationId)
    {
        AppNotification::where('id', $notificationId)
            ->where('user_id', $request->user()->id)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function getPerformanceStats(Request $request)
    {
        $driverId = $request->user()->id;
        $today = $this->manilaToday();
        $weekAgo = Carbon::now('Asia/Manila')->subDays(7)->toDateString();

        $weekly = RouteAssignment::where('driver_id', $driverId)
            ->whereBetween('assigned_date', [$weekAgo, $today])
            ->selectRaw("
                COUNT(*) as total_assigned,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                ROUND((SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as completion_rate
            ")
            ->first();

        $todayStats = RouteAssignment::where('driver_id', $driverId)
            ->where('assigned_date', $today)
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->first();

        return response()->json([
            'weekly'             => $weekly ?? ['total_assigned' => 0, 'completed' => 0, 'pending' => 0, 'cancelled' => 0, 'completion_rate' => 0],
            'today'              => $todayStats ?? ['total' => 0, 'completed' => 0],
            'connected_vehicles' => 0,
        ]);
    }

    public function getOpenReports()
    {
        $reports = Report::with('resident:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(fn($q) => $q->whereNull('status')->orWhereIn('status', ['pending', 'in_progress']))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn($r) => [
                'id'            => $r->id,
                'type'          => $r->type,
                'description'   => $r->description,
                'latitude'      => $r->latitude,
                'longitude'     => $r->longitude,
                'image_url'     => $r->image_url,
                'location_note' => $r->location_note,
                'created_at'    => $r->created_at,
                'status'        => $r->status,
                'user_id'       => $r->resident_id,
                'resident_name' => $r->resident->name ?? null,
            ]);

        return response()->json($reports);
    }

    public function updateReportStatus(Request $request, $id)
    {
        $status = $request->input('status');
        $nextStatus = $status === 'completed' ? 'resolved' : $status;

        $allowed = ['pending', 'in_progress', 'resolved', 'rejected'];
        if (!in_array($nextStatus, $allowed, true)) {
            return response()->json(['error' => "Invalid status: {$nextStatus}. Allowed: " . implode(', ', $allowed)], 400);
        }

        $report = Report::find($id);
        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        $report->update([
            'status'      => $nextStatus,
            'resolved_by' => $request->user()->id,
        ]);

        broadcast(new RealtimeEvent('reports', 'report-status-changed', [
            'reportId'   => (int) $id,
            'status'     => $nextStatus,
            'resolvedBy' => $request->user()->id,
        ]));

        return response()->json(['message' => "Report marked as {$nextStatus}", 'status' => $nextStatus]);
    }
}
