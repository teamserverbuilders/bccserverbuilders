<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\PasswordOtpMail;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            $this->logLoginAttempt($request, null, false, 'failed');

            if ($user) {
                $user->increment('failed_login_attempts');
                if ($user->failed_login_attempts >= 5) {
                    $user->update(['locked_until' => now()->addMinutes(30)]);
                }
            }

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->isLocked()) {
            $this->logLoginAttempt($request, $user, false, 'locked');
            return response()->json(['message' => 'Account is locked. Try again later.'], 423);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Account is not active.'], 403);
        }

        $user->update([
            'failed_login_attempts' => 0,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $this->logLoginAttempt($request, $user, true, 'login');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->toAuthArray(),
        ]);
    }

    public function logout(Request $request)
    {
        $this->logLoginAttempt($request, $request->user(), true, 'logout');
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->toAuthArray());
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $request->user()->update([
            'password' => Hash::make($request->password),
            'force_password_change' => false,
        ]);

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:20',
        ]);

        $request->user()->update($request->only(['name', 'contact_number']));

        return response()->json($request->user()->fresh());
    }

    public function sendPasswordOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower($data['email']);
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['No account was found for this email address.'],
            ]);
        }

        $sendKey = 'password-otp-sends:'.$email;
        $sends = (int) Cache::get($sendKey, 0);
        if ($sends >= 5) {
            return response()->json([
                'message' => 'Too many code requests. Please wait a few minutes and try again.',
            ], 429);
        }

        $otp = (string) random_int(100000, 999999);
        Cache::put('password-otp:'.$email, [
            'hash' => Hash::make($otp),
            'attempts' => 0,
        ], now()->addMinutes(10));
        Cache::put($sendKey, $sends + 1, now()->addMinutes(15));

        try {
            Mail::to($user->email)->send(new PasswordOtpMail($user->name, $otp));
        } catch (\Throwable $e) {
            Cache::forget('password-otp:'.$email);
            report($e);

            return response()->json([
                'message' => 'The account exists, but the reset code could not be emailed. Please try again.',
            ], 500);
        }

        return response()->json([
            'message' => 'A verification code was sent to your email.',
        ]);
    }

    public function verifyPasswordOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $email = strtolower($data['email']);
        $key = 'password-otp:'.$email;
        $record = Cache::get($key);

        if (!$record) {
            throw ValidationException::withMessages([
                'otp' => ['The code has expired. Request a new one.'],
            ]);
        }

        if (($record['attempts'] ?? 0) >= 5) {
            Cache::forget($key);
            throw ValidationException::withMessages([
                'otp' => ['Too many incorrect attempts. Request a new code.'],
            ]);
        }

        if (!Hash::check($data['otp'], $record['hash'])) {
            $record['attempts'] = ($record['attempts'] ?? 0) + 1;
            Cache::put($key, $record, now()->addMinutes(10));
            throw ValidationException::withMessages([
                'otp' => ['The code is incorrect.'],
            ]);
        }

        $resetToken = Str::random(64);
        Cache::put('password-reset:'.$email, Hash::make($resetToken), now()->addMinutes(15));
        Cache::forget($key);

        return response()->json([
            'message' => 'Code verified.',
            'reset_token' => $resetToken,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'reset_token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = strtolower($data['email']);
        $key = 'password-reset:'.$email;
        $hash = Cache::get($key);

        if (!$hash || !Hash::check($data['reset_token'], $hash)) {
            throw ValidationException::withMessages([
                'password' => ['Your reset session expired. Start again from your email.'],
            ]);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['No account was found for this email address.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
            'force_password_change' => false,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
        Cache::forget($key);

        return response()->json([
            'message' => 'Password updated. You can sign in now.',
        ]);
    }

    private function logLoginAttempt(Request $request, ?User $user, bool $success, string $action): void
    {
        LoginLog::create([
            'user_id' => $user?->id,
            'email' => $request->email ?? $user?->email,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'success' => $success,
        ]);
    }
}

