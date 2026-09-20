<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MrfLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MRFController extends Controller
{
    public function index()
    {
        $locations = MrfLocation::orderBy('created_at', 'desc')->get();
        return response()->json($locations);
    }

    public function show($id)
    {
        $location = MrfLocation::find($id);

        if (!$location) {
            return response()->json(['error' => 'MRF location not found'], 404);
        }

        return response()->json($location);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:150',
            'address'   => 'nullable|string|max:255',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $location = MrfLocation::create([
            'name'       => trim($request->name),
            'address'    => $request->address ? trim($request->address) : null,
            'latitude'   => $request->latitude,
            'longitude'  => $request->longitude,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'MRF location marked successfully',
            'id'      => $location->id,
            'data'    => $location,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $location = MrfLocation::find($id);

        if (!$location) {
            return response()->json(['error' => 'MRF location not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'      => 'sometimes|required|string|max:150',
            'address'   => 'nullable|string|max:255',
            'latitude'  => 'sometimes|required|numeric',
            'longitude' => 'sometimes|required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = [];

        if ($request->has('name')) {
            $data['name'] = trim($request->name);
        }
        if ($request->has('address')) {
            $data['address'] = $request->address ? trim($request->address) : null;
        }
        if ($request->has('latitude')) {
            $data['latitude'] = $request->latitude;
        }
        if ($request->has('longitude')) {
            $data['longitude'] = $request->longitude;
        }

        if (empty($data)) {
            return response()->json(['error' => 'Nothing to update'], 400);
        }

        $location->update($data);

        return response()->json(['message' => 'MRF location updated successfully']);
    }

    public function destroy($id)
    {
        $location = MrfLocation::find($id);

        if (!$location) {
            return response()->json(['error' => 'MRF location not found'], 404);
        }

        $location->delete();

        return response()->json(['message' => 'MRF location deleted successfully']);
    }
}