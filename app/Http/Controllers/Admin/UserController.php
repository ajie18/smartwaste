<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RouteAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'name', 'email', 'role', 'phone', 'address')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::select('id', 'name', 'email', 'role', 'phone', 'address')
            ->find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'nullable|in:admin,driver,resident',
            'phone'    => 'nullable|string|max:20',
            'address'  => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name'           => $request->name,
            'email'          => strtolower(trim($request->email)),
            'password'       => Hash::make($request->password),
            'role'           => $request->role ?? 'resident',
            'phone'          => $request->phone,
            'address'        => $request->address,
            'email_verified' => true, // admin-created users are auto-verified
        ]);

        // Optional: Audit log
        // AuditLog::create([...]);

        return response()->json([
            'message' => 'User created and auto-verified. They can sign in immediately.',
            'user'    => $user->only(['id', 'name', 'email', 'role', 'phone', 'address']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'     => 'nullable|in:admin,driver,resident',
            'phone'    => 'nullable|string|max:20',
            'address'  => 'nullable|string|max:500',
            'password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = [
            'name'    => $request->name,
            'email'   => strtolower(trim($request->email)),
            'role'    => $request->role ?? $user->role,
            'phone'   => $request->phone,
            'address' => $request->address,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json(['message' => 'User updated successfully']);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Prevent deleting yourself
        if ($user->id === $request->user()->id) {
            return response()->json(['error' => 'You cannot delete your own account'], 400);
        }

        // Check if driver has active assignments
        $hasActive = RouteAssignment::where('driver_id', $id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();

        if ($hasActive) {
            return response()->json([
                'error' => 'Cannot delete — user has active route assignments'
            ], 409);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}