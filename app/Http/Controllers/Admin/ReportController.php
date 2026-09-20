<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with('resident:id,name,email')
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $perPage = $request->get('limit', 20);
        $reports = $query->paginate($perPage);

        // Format image_url if needed
        $reports->getCollection()->transform(function ($report) {
            if ($report->image_url && !str_starts_with($report->image_url, 'data:image') && !str_starts_with($report->image_url, 'http')) {
                $report->image_url = 'data:image/jpeg;base64,' . $report->image_url;
            }
            return $report;
        });

        return response()->json([
            'data' => $reports->items(),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'per_page'     => $reports->perPage(),
                'total'        => $reports->total(),
                'total_pages'  => $reports->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $report = Report::with('resident:id,name,email,phone')->find($id);

        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        if ($report->image_url && !str_starts_with($report->image_url, 'data:image') && !str_starts_with($report->image_url, 'http')) {
            $report->image_url = 'data:image/jpeg;base64,' . $report->image_url;
        }

        return response()->json($report);
    }

    public function updateStatus(Request $request, $id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status'      => 'required|in:pending,in_progress,resolved,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $report->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes ?? $report->admin_notes,
        ]);

        $report->load('resident:id,name');

        return response()->json([
            'message' => 'Report updated successfully',
            'report'  => $report,
        ]);
    }

    public function destroy($id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        $report->delete();

        // Optional: Audit log

        return response()->json(['message' => 'Report deleted successfully']);
    }

    public function analytics()
    {
        $typeBreakdown = Report::selectRaw('type, COUNT(*) as total, MIN(created_at) as first_report, MAX(created_at) as last_report')
            ->groupBy('type')
            ->get();

        $statusBreakdown = Report::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        $total = Report::count();

        return response()->json([
            'type_breakdown'   => $typeBreakdown,
            'status_breakdown' => $statusBreakdown,
            'total_reports'    => $total,
        ]);
    }
}