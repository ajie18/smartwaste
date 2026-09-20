<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Truck;
use App\Models\Route;
use App\Models\Report;
use App\Models\TripLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalUsers   = User::count();
        $totalDrivers = User::where('role', 'driver')->count();
        $totalTrucks  = Truck::count();
        $totalRoutes  = Route::count();
        $totalReports = Report::count();
        $totalFuel    = TripLog::sum('fuel_used') ?? 0;

        return response()->json([
            'totalUsers'     => $totalUsers,
            'totalDrivers'   => $totalDrivers,
            'totalTrucks'    => $totalTrucks,
            'totalRoutes'    => $totalRoutes,
            'totalReports'   => $totalReports,
            'totalFuelUsed'  => (float) $totalFuel,
        ]);
    }
}