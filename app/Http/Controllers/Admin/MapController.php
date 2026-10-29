<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MrfLocation;
use App\Models\Route;
use App\Models\Truck;

class MapController extends Controller
{
    public function index()
    {
        $mapData = [
            'trucks' => Truck::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get(['id', 'plate_number', 'model', 'latitude', 'longitude', 'location_updated_at'])
                ->toArray(),
            'routes' => Route::query()
                ->get(['id', 'zone_name', 'waypoints_json'])
                ->toArray(),
            'mrfLocations' => MrfLocation::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get(['id', 'name', 'address', 'latitude', 'longitude'])
                ->toArray(),
        ];

        return view('admin.map', [
            'mapData' => $mapData,
            'tileUrl' => config('services.map.tile_url'),
        ]);
    }
}
