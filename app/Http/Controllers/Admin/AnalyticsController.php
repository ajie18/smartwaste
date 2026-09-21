<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{

    public function index()
    {
        $typeBreakdown = Report::selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->get();

        $statusBreakdown = Report::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        return response()->json([
            'type_breakdown'   => $typeBreakdown,
            'status_breakdown' => $statusBreakdown,
            'total_reports'    => Report::count(),
        ]);
    }


    public function export()
    {
        return $this->exportCsv();
    }

    private function exportCsv(): StreamedResponse
    {
        $data = Report::selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->get();

        $filename = 'analytics-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
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
