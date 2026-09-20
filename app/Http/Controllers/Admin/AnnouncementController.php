<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Events\RealtimeEvent;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('creator:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id'         => $item->id,
                    'title'      => $item->title,
                    'message'    => $item->message,
                    'image_url'  => $item->image_url,
                    'pickup_date' => $item->pickup_date,
                    'created_at' => $item->created_at,
                    'admin_name' => $item->creator->name ?? 'Admin',
                ];
            });

        return response()->json($announcements);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'message'     => 'required|string',
            'pickup_date' => 'nullable|date',
            'image_url'   => 'nullable|string', // can be base64 or already uploaded path
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imagePath = null;

        // Handle base64 image (same as your Node version)
        if ($request->filled('image_url') && str_starts_with($request->image_url, 'data:image')) {
            try {
                $imagePath = $this->saveBase64Image($request->image_url);
            } catch (\Exception $e) {
                // Continue without image if saving fails
                $imagePath = null;
            }
        } elseif ($request->filled('image_url')) {
            $imagePath = $request->image_url;
        }

        $announcement = Announcement::create([
            'title'       => $request->title,
            'message'     => $request->message,
            'pickup_date' => $request->pickup_date,
            'image_url'   => $imagePath,
            'created_by'  => $request->user()->id,
        ]);

        // Live-pushes to every connected driver/resident dashboard
        // (driver-dashboard.js's socket.on('new-announcement') /
        // echo-bridge.js), same as the old socket.io broadcast.
        broadcast(new RealtimeEvent('reports', 'new-announcement', [
            'id'         => $announcement->id,
            'title'      => $announcement->title,
            'message'    => $announcement->message,
            'pickup_date' => $announcement->pickup_date,
            'created_at' => $announcement->created_at,
        ]));

        return response()->json([
            'message'   => 'Announcement created successfully',
            'image_url' => $imagePath,
            'data'      => $announcement,
        ], 201);
    }

    public function destroy($id)
    {
        $announcement = Announcement::find($id);

        if (!$announcement) {
            return response()->json(['error' => 'Announcement not found'], 404);
        }

        // Optional: delete the image file if it exists
        if ($announcement->image_url && !str_starts_with($announcement->image_url, 'http')) {
            $path = str_replace('/storage/', '', $announcement->image_url);
            Storage::disk('public')->delete($path);
        }

        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted successfully']);
    }

    /**
     * Save base64 image and return public URL
     */
    private function saveBase64Image(string $base64): string
    {
        preg_match('/^data:image\/(\w+);base64,/', $base64, $matches);
        $extension = $matches[1] ?? 'jpg';
        if ($extension === 'jpeg') $extension = 'jpg';

        $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64));

        $filename = 'announcements/' . time() . '-' . Str::random(8) . '.' . $extension;

        Storage::disk('public')->put($filename, $imageData);

        return Storage::url($filename); // returns /storage/announcements/...
    }
}