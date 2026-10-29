<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Route;
use App\Models\RouteAssignment;
use App\Models\Truck;
use App\Services\GeminiAiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnexpectedValueException;

class AnalyticsController extends Controller
{
    /**
     * Simple analytics (already exists in ReportController, but kept here too)
     */
    public function index()
    {
        $typeBreakdown = Report::selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->get();

        $statusBreakdown = Report::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        return response()->json([
            'type_breakdown' => $typeBreakdown,
            'status_breakdown' => $statusBreakdown,
            'total_reports' => Report::count(),
        ]);
    }

    public function aiInsights(GeminiAiService $gemini)
    {
        $since = now()->subDays(30);
        $assignmentSince = $since->toDateString();

        $summary = [
            'period' => [
                'start' => $since->toDateString(),
                'end' => now()->toDateString(),
            ],
            'reports' => [
                'total' => Report::where('created_at', '>=', $since)->count(),
                'by_type' => Report::where('created_at', '>=', $since)
                    ->selectRaw('type, COUNT(*) as total')
                    ->groupBy('type')
                    ->pluck('total', 'type'),
                'by_status' => Report::where('created_at', '>=', $since)
                    ->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status'),
            ],
            'pickups' => [
                'total' => RouteAssignment::whereDate('assigned_date', '>=', $assignmentSince)->count(),
                'by_status' => RouteAssignment::whereDate('assigned_date', '>=', $assignmentSince)
                    ->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status'),
            ],
            'fleet' => [
                'total_trucks' => Truck::count(),
                'trucks_with_location' => Truck::whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->count(),
                'total_routes' => Route::count(),
            ],
        ];

        try {
            $insights = $gemini->generateAnalyticsInsights($summary);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'error' => 'AI insights are not configured. Set GEMINI_API_KEY on the server.',
            ], 503);
        } catch (ConnectionException|RequestException|UnexpectedValueException $exception) {
            Log::warning('Gemini analytics insight request failed.', [
                'exception' => $exception::class,
                'upstream_status' => $exception instanceof RequestException
                    ? $exception->response->status()
                    : null,
            ]);

            return response()->json([
                'error' => 'The AI provider could not generate analytics insights. Please try again later.',
            ], 502);
        }

        return response()->json([
            'insights' => $insights,
            'model' => config('services.gemini.model'),
            'period' => $summary['period'],
        ]);
    }

    /**
     * Export analytics to Excel (.xlsx)
     * Requires: composer require maatwebsite/excel
     */
    public function export()
    {
        // If you prefer a simple CSV for now (no extra package):
        return $this->exportCsv();

        // Later you can switch to Maatwebsite\Excel
    }

    private function exportCsv(): StreamedResponse
    {
        $data = Report::selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->get();

        $filename = 'analytics-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Report Type', 'Total Reports']);

            foreach ($data as $row) {
                fputcsv($file, [$row->type, $row->total]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
