<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Truck;
use App\Models\RouteAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TruckController extends Controller
{
    public function index()
    {
        $trucks = Truck::orderBy('id', 'desc')->get();
        return response()->json($trucks);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plate_number' => 'required|string|max:50|unique:trucks,plate_number',
            'model'        => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $truck = Truck::create([
            'plate_number' => $request->plate_number,
            'model'        => $request->model,
        ]);

        return response()->json([
            'message' => 'Truck added successfully',
            'truck'   => $truck,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $truck = Truck::find($id);

        if (!$truck) {
            return response()->json(['error' => 'Truck not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'plate_number' => 'required|string|max:50|unique:trucks,plate_number,' . $id,
            'model'        => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $truck->update([
            'plate_number' => $request->plate_number,
            'model'        => $request->model,
        ]);

        return response()->json(['message' => 'Truck updated successfully']);
    }

    public function destroy($id)
    {
        $truck = Truck::find($id);

        if (!$truck) {
            return response()->json(['error' => 'Truck not found'], 404);
        }

        $hasActive = RouteAssignment::where('truck_id', $id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();

        if ($hasActive) {
            return response()->json([
                'error' => 'Cannot delete — truck is assigned to an active or pending route.'
            ], 409);
        }

        $truck->delete();

        return response()->json(['message' => 'Truck deleted successfully']);
    }

    public function updateLocation(Request $request, $id)
    {
        $truck = Truck::find($id);

        if (!$truck) {
            return response()->json(['error' => 'Truck not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $truck->update([
            'latitude'            => $request->latitude,
            'longitude'           => $request->longitude,
            'location_updated_at' => now(),
        ]);

        return response()->json(['message' => 'Location updated successfully']);
    }
}