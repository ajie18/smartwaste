<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\IprogSmsService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AuthController extends Controller
{
    /**
     * Register a new user (auto-verified)
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'role' => 'nullable|in:resident,driver,admin',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
            'role' => $request->role ?? 'resident',
            'email_verified' => true,          // auto-verified like your Node version
        ]);

        // Optional: log action
        // AuditLog::create([...]);

        return response()->json([
            'message' => 'Account created successfully. You can sign in now.',
            'userId' => $user->id,
            'email' => $user->email,
            'requiresVerification' => false,
        ], 201);
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Optional: account lock check
        if ($user->locked_at && Carbon::parse($user->locked_at)->gt(now()->subMinutes(30))) {
            return response()->json([
                'error' => 'Account locked',
                'message' => 'Too many failed attempts. Please try again later.',
            ], 403);
        }

        // Create token (Laravel Sanctum)
        $token = $user->createToken('auth_token')->plainTextToken;

        // Update last login
        $user->update([
            'last_login' => now(),
            'last_ip' => $request->ip(),
        ]);

        return response()->json([
            'token' => $token,
            'role' => $user->role,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Forgot Password – send SMS PIN
     */
    public function forgotPassword(Request $request, IprogSmsService $smsService)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', strtolower(trim($request->email)))->first();

        // Always return the same message (security)
        $generic = [
            'message' => 'If that account exists and has a phone number on file, the reset request was processed.',
        ];

        if (! $user || ! $user->phone) {
            return response()->json($generic);
        }

        $pin = random_int(100000, 999999);
        $expires = now()->addMinutes(15);

        $user->forceFill([
            'reset_pin' => $pin,
            'reset_pin_expires' => $expires,
            'reset_attempts' => 0,
        ])->save();

        try {
            $smsService->send($user->phone, "Your SmartWaste password reset PIN is {$pin}. It expires in 15 minutes.");
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            $user->forceFill([
                'reset_pin' => null,
                'reset_pin_expires' => null,
            ])->save();

            Log::error('Unable to deliver a password reset SMS.', [
                'exception' => $exception::class,
            ]);
        }

        return response()->json($generic);
    }

    /**
     * Reset Password with PIN
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'pin' => 'required|string',
            'newPassword' => 'required|string|min:8',
        ]);

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (! $user) {
            return response()->json(['error' => 'Invalid email or PIN'], 400);
        }

        if ($user->reset_attempts >= 5) {
            return response()->json(['error' => 'Too many attempts. Request a new PIN.'], 429);
        }

        if (! $user->reset_pin || ! $user->reset_pin_expires || now()->gt($user->reset_pin_expires)) {
            return response()->json(['error' => 'PIN expired or invalid. Request a new one.'], 400);
        }

        if ($user->reset_pin !== $request->pin) {
            $user->increment('reset_attempts');

            return response()->json(['error' => 'Invalid PIN'], 400);
        }

        $user->forceFill([
            'password' => Hash::make($request->newPassword),
            'reset_pin' => null,
            'reset_pin_expires' => null,
            'reset_attempts' => 0,
            'password_updated_at' => now(),
        ])->save();

        return response()->json(['message' => 'Password updated successfully. You can now sign in.']);
    }

    /**
     * Change Password (authenticated)
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:8',
        ]);

        $user = $request->user();

        if (! Hash::check($request->currentPassword, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 401);
        }

        $user->update([
            'password' => Hash::make($request->newPassword),
            'password_updated_at' => now(),
        ]);

        return response()->json(['message' => 'Password changed successfully']);
    }

    /**
     * Get Profile
     */
    public function getProfile(Request $request)
    {
        $user = $request->user()->only(['id', 'name', 'email', 'role', 'phone', 'address', 'last_login']);

        return response()->json($user);
    }

    /**
     * Update Profile
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $request->user()->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return response()->json(['message' => 'Profile updated successfully']);
    }
}
