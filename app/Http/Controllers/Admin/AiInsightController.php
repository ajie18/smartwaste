<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Route;
use App\Models\RouteAssignment;
use App\Models\TripLog;
use App\Models\Truck;
use App\Models\User;
use App\Services\AiInsightService;
use Illuminate\Http\Request;

class AiInsightController extends Controller
{
    public function __construct(private AiInsightService $ai) {}

    public function analyticsInsight()
    {
        $typeBreakdown = Report::selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->get();

        $statusBreakdown = Report::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        $last30 = Report::where('created_at', '>=', now()->subDays(30))->count();
        $prev30 = Report::whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count();

        $pickupStats = RouteAssignment::where('assigned_date', '>=', now()->subDays(30)->toDateString())
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            ")
            ->first();

        $fuelUsedLast30 = TripLog::where('logged_at', '>=', now()->subDays(30))->sum('fuel_used');

        $payload = [
            'report_type_breakdown'          => $typeBreakdown,
            'report_status_breakdown'        => $statusBreakdown,
            'total_reports_all_time'         => Report::count(),
            'reports_last_30_days'           => $last30,
            'reports_prior_30_days'          => $prev30,
            'report_volume_change_pct'       => $prev30 > 0 ? round((($last30 - $prev30) / $prev30) * 100, 1) : null,
            'pickup_stats_last_30_days'      => $pickupStats,
            'fuel_used_last_30_days_liters'  => (float) $fuelUsedLast30,
            'fleet_size'                     => Truck::count(),
            'active_routes'                  => Route::count(),
            'total_drivers'                  => User::where('role', 'driver')->count(),
            'total_residents'                => User::where('role', 'resident')->count(),
        ];

        $insight = $this->ai->summarizeAnalytics($payload);

        return response()->json([
            'insight'   => $insight,
            'data'      => $payload,
            'generated_at' => now(),
        ]);
    }

    public function triageReport($id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        $suggestion = $this->ai->triageReport($report->type, $report->description);

        return response()->json([
            'report_id' => $report->id,
            'suggestion' => $suggestion,
        ]);
    }
}
