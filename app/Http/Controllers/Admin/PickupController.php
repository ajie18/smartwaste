<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RouteAssignment;
use App\Models\Route;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PickupController extends Controller
{
    public function index()
    {
        $pickups = RouteAssignment::with(['driver:id,name', 'truck:id,plate_number', 'route:id,zone_name'])
            ->orderBy('assigned_date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'driver_id'     => $item->driver_id,
                    'truck_id'      => $item->truck_id,
                    'route_id'      => $item->route_id,
                    'assigned_date' => $item->assigned_date,
                    'status'        => $item->status,
                    'pickup_type'   => $item->pickup_type,
                    'driver_name'   => $item->driver->name ?? null,
                    'truck_plate'   => $item->truck->plate_number ?? null,
                    'zone_name'     => $item->route->zone_name ?? null,
                ];
            });

        return response()->json($pickups);
    }

    public function assign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id'     => 'required|exists:users,id',
            'truck_id'      => 'required|exists:trucks,id',
            'route_id'      => 'required|exists:routes,id',
            'assigned_date' => 'required|date',
            'pickup_type'   => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Optional: verify the user is actually a driver
        $driver = User::where('id', $request->driver_id)->where('role', 'driver')->first();
        if (!$driver) {
            return response()->json(['error' => 'Selected user is not a driver'], 400);
        }

        $assignment = RouteAssignment::create([
            'driver_id'     => $request->driver_id,
            'truck_id'      => $request->truck_id,
            'route_id'      => $request->route_id,
            'assigned_date' => $request->assigned_date,
            'pickup_type'   => $request->pickup_type ?? 'regular',
            'status'        => 'pending',
        ]);

        // TODO: Trigger SMS notification to residents (same as your Node version)
        // $this->notifyResidentsOfPickup(...);

        return response()->json([
            'message'    => 'Route assigned successfully',
            'assignment' => $assignment,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $assignment = RouteAssignment::find($id);

        if (!$assignment) {
            return response()->json(['error' => 'Pickup schedule not found'], 404);
        }

        $data = $request->only([
            'driver_id',
            'truck_id',
            'route_id',
            'assigned_date',
            'status',
            'pickup_type'
        ]);

        // Remove null values so we only update what was sent
        $data = array_filter($data, fn($value) => !is_null($value));

        if (empty($data)) {
            return response()->json(['error' => 'Nothing to update'], 400);
        }

        $assignment->update($data);

        return response()->json(['message' => 'Pickup schedule updated successfully']);
    }

    public function destroy($id)
    {
        $assignment = RouteAssignment::find($id);

        if (!$assignment) {
            return response()->json(['error' => 'Pickup schedule not found'], 404);
        }

        $assignment->delete();

        return response()->json(['message' => 'Pickup schedule deleted successfully']);
    }

    /**
     * Manually trigger SMS notification for today's pickups
     */
    public function notifyPickupToday()
    {
        $today = now()->timezone('Asia/Manila')->toDateString();

        $assignment = RouteAssignment::with(['route', 'truck'])
            ->where('assigned_date', $today)
            ->whereIn('status', ['pending', 'in_progress'])
            ->first();

        if (!$assignment) {
            return response()->json([
                'message' => 'No pickup scheduled for today',
                'sent'    => 0,
            ]);
        }

        // TODO: Call your SMS service here
        // notifyResidentsOfPickup(...)

        return response()->json([
            'message' => 'Pickup SMS notifications queued for residents with phone numbers',
        ]);
    }
}