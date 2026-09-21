<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\RouteAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RouteController extends Controller
{
    public function index()
    {
        $routes = Route::orderBy('created_at', 'desc')->get();

        $routes->transform(function ($route) {
            if (is_string($route->waypoints_json)) {
                $route->waypoints_json = json_decode($route->waypoints_json, true);
            }
            return $route;
        });

        return response()->json($routes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'zone_name'      => 'required|string|max:255',
            'description'    => 'nullable|string',
            'waypoints_json' => 'nullable',
            'path_mode'      => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $waypoints = $this->normalizeWaypoints($request->waypoints_json);

        $route = Route::create([
            'zone_name'      => $request->zone_name,
            'description'    => $request->description,
            'waypoints_json' => $waypoints,
            'path_mode'      => $request->path_mode ?? 'pin',
        ]);

        return response()->json([
            'message' => 'Route added successfully',
            'route'   => $route,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $route = Route::find($id);

        if (!$route) {
            return response()->json(['error' => 'Route not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'zone_name'      => 'required|string|max:255',
            'description'    => 'nullable|string',
            'waypoints_json' => 'nullable',
            'path_mode'      => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $waypoints = $this->normalizeWaypoints($request->waypoints_json);

        $route->update([
            'zone_name'      => $request->zone_name,
            'description'    => $request->description,
            'waypoints_json' => $waypoints,
            'path_mode'      => $request->path_mode ?? $route->path_mode,
        ]);

        return response()->json(['message' => 'Route updated successfully']);
    }

    public function destroy($id)
    {
        $route = Route::find($id);

        if (!$route) {
            return response()->json(['error' => 'Route not found'], 404);
        }

        $hasActive = RouteAssignment::where('route_id', $id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();

        if ($hasActive) {
            return response()->json([
                'error' => 'Cannot delete — route is assigned to an active or pending schedule.'
            ], 409);
        }

        $route->delete();

        return response()->json(['message' => 'Route deleted successfully']);
    }

    private function normalizeWaypoints($waypoints)
    {
        if (empty($waypoints)) {
            return json_encode(['road' => [], 'pins' => [], 'mrf' => []]);
        }

        if (is_string($waypoints)) {
            $decoded = json_decode($waypoints, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return json_encode(['road' => [], 'pins' => [], 'mrf' => []]);
            }
            $waypoints = $decoded;
        }

        // If it's already in the correct structure
        if (isset($waypoints['road']) || isset($waypoints['pins']) || isset($waypoints['mrf'])) {
            return json_encode($waypoints);
        }

        // If it's a plain array, treat as pins
        if (is_array($waypoints)) {
            return json_encode([
                'road' => [],
                'pins' => $waypoints,
                'mrf'  => [],
            ]);
        }

        return json_encode(['road' => [], 'pins' => [], 'mrf' => []]);
    }
}
