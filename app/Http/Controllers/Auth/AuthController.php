<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(private SmsService $sms) {}

    private function logAction(?int $userId, string $action, Request $request): void
    {
        try {
            AuditLog::create([
                'user_id'    => $userId,
                'action'     => $action,
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email',
            'password' => 'required|string|min:8',
            'phone'    => 'required|string|max:20',
            'role'     => 'nullable|in:admin,driver,resident',
            'address'  => 'nullable|string|max:500',
        ], [
            'phone.required' => 'Phone number is required for SMS password recovery',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $cleanEmail = strtolower(trim($request->email));

        if (User::where('email', $cleanEmail)->exists()) {
            return response()->json(['error' => 'Email already registered'], 409);
        }

        $user = User::create([
            'name'           => $request->name,
            'email'          => $cleanEmail,
            'password'       => Hash::make($request->password),
            'role'           => $request->role ?? 'resident',
            'phone'          => $request->phone,
            'address'        => $request->address,
            'email_verified' => true,
        ]);
        $user->assignRole($user->role);

        $this->logAction($user->id, 'User registered (auto-verified)', $request);

        return response()->json([
            'message'              => 'Account created successfully. You can sign in now.',
            'userId'               => $user->id,
            'email'                => $cleanEmail,
            'requiresVerification' => false,
        ], 201);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid email format'], 400);
        }

        $generic = [
            'message' => 'If that account exists and has a phone number on file, a PIN was sent by SMS.',
        ];

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (!$user) {
            return response()->json($generic);
        }

        if (!$user->phone) {
            report(new \Exception("Forgot password: user {$user->id} has no phone number"));
            return response()->json($generic);
        }

        $pin = (string) random_int(100000, 999999);

        $user->forceFill([
            'reset_pin'         => $pin,
            'reset_pin_expires' => now()->addMinutes(15),
            'reset_attempts'    => 0,
        ])->save();

        $smsResult = $this->sms->sendOTP($user->phone, $pin, 'reset');

        if (!$smsResult['ok']) {
            return response()->json([
                'error' => 'Failed to send reset PIN by SMS. Please try again shortly or contact support.',
            ], 500);
        }

        $this->logAction($user->id, 'Password reset PIN requested (SMS)', $request);

        return response()->json($generic);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'       => 'required|email',
            'pin'         => 'required|string',
            'newPassword' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid email or PIN'], 400);
        }

        if ($user->reset_attempts >= 5) {
            return response()->json(['error' => 'Too many attempts. Request a new PIN.'], 429);
        }

        if (!$user->reset_pin || !$user->reset_pin_expires || $user->reset_pin_expires->isPast()) {
            return response()->json(['error' => 'PIN expired or invalid. Request a new one.'], 400);
        }

        if ($user->reset_pin !== trim($request->pin)) {
            $user->increment('reset_attempts');
            return response()->json(['error' => 'Invalid PIN'], 400);
        }

        $user->forceFill([
            'password'            => Hash::make($request->newPassword),
            'reset_pin'           => null,
            'reset_pin_expires'   => null,
            'reset_attempts'      => 0,
            'password_updated_at' => now(),
        ])->save();

        $this->logAction($user->id, 'Password reset completed', $request);

        return response()->json(['message' => 'Password updated successfully. You can now sign in.']);
    }


    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Email and password required'], 400);
        }

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        if ($user->locked_at && $user->locked_at->gt(now()->subMinutes(30))) {
            return response()->json([
                'error'   => 'Account locked',
                'message' => 'Too many failed attempts. Please try again later.',
            ], 403);
        }

        $token = $user->createToken('api-token', ['*'], now()->addDay())->plainTextToken;

        $user->forceFill([
            'last_login' => now(),
            'last_ip'    => $request->ip(),
        ])->save();

        return response()->json([
            'token' => $token,
            'role'  => $user->role,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            $this->logAction($request->user()->id, 'User logged out', $request);
            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Logout failed'], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currentPassword' => 'required|string',
            'newPassword'     => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Current and new password required'], 400);
        }

        $user = $request->user();

        if (!Hash::check($request->currentPassword, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 401);
        }

        $user->forceFill([
            'password'            => Hash::make($request->newPassword),
            'password_updated_at' => now(),
        ])->save();

        $this->logAction($user->id, 'Password changed', $request);

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function getProfile(Request $request)
    {
        return response()->json(
            $request->user()->only(['id', 'name', 'email', 'role', 'phone', 'address', 'last_login'])
        );
    }

    public function updateProfile(Request $request)
    {
        $request->user()->forceFill([
            'name'    => $request->name,
            'phone'   => $request->phone ?: null,
            'address' => $request->address ?: null,
        ])->save();

        $this->logAction($request->user()->id, 'Profile updated', $request);

        return response()->json(['message' => 'Profile updated successfully']);
    }

    public function verifyEmailPin()
    {
        return response()->json(['message' => 'Email verification is no longer required. You can sign in.']);
    }

    public function verifyEmailLink()
    {
        return redirect('/login?verified=1');
    }

    public function resendVerification()
    {
        return response()->json(['message' => 'Email verification is no longer required. You can sign in.']);
    }
}
