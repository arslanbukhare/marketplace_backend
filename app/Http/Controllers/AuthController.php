<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\IndividualProfile;
use App\Models\CompanyProfile;
use App\Services\OtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status' => 'active', 
            ]);

            // Create the profile based on role
            if ($request->role === 'individual') {
                IndividualProfile::create([
                    'user_id' => $user->id,
                    'first_name' => $request->first_name,
                ]);
            } elseif ($request->role === 'company') {
                CompanyProfile::create([
                    'user_id' => $user->id,
                    'company_name' => $request->company_name,
                ]);
            }

            // Send Laravel email verification link
            event(new Registered($user));

            DB::commit();

            return response()->json([
                'message' => 'Registration successful. Please verify your email address.'
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Registration failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function requestLoginOtp(Request $request)
    // {
    //     $request->validate([
    //         'login' => 'required|string',
    //     ]);

    //     $login = $request->login;

    //     $hourKey = "otp-hour:$login";
    //     $dayKey = "otp-day:$login";
    //     $hourLimit = 5;
    //     $dayLimit = 7;

    //     if (RateLimiter::tooManyAttempts($hourKey, $hourLimit)) {
    //         $seconds = RateLimiter::availableIn($hourKey);
    //         return response()->json([
    //             'message' => 'Too many attempts. Please try again after one hour.',
    //             'retry_after_seconds' => $seconds,
    //             'type' => 'hourly'
    //         ], 429);
    //     }

    //     if (RateLimiter::tooManyAttempts($dayKey, $dayLimit)) {
    //         $seconds = RateLimiter::availableIn($dayKey);
    //         return response()->json([
    //             'message' => 'Daily limit reached. Please try again after 24 hours.',
    //             'retry_after_seconds' => $seconds,
    //             'type' => 'daily'
    //         ], 429);
    //     }

    //     $user = User::where('email', $login)
    //                 ->orWhere('phone', $login)
    //                 ->first();

    //     if (!$user) {
    //         return response()->json(['message' => 'User not found'], 404);
    //     }

    //     // Check email verification (Laravel default column is email_verified_at)
    //     if (!$user->hasVerifiedEmail()) {
    //         return response()->json([
    //             'message' => 'Your email is not verified. Please verify it before logging in.',
    //             'status' => 'unverified',
    //         ], 403);
    //     }

    //     // Hit rate limits
    //     RateLimiter::hit($hourKey, 3600);
    //     RateLimiter::hit($dayKey, 86400);

    //     // Generate OTP
    //     $otp = rand(100000, 999999);
    //     $user->login_otp = $otp;
    //     $user->otp_expires_at = now()->addMinutes(5);
    //     $user->save();

    //     logger("Sending OTP $otp to $login");

    //     return response()->json(['message' => 'OTP sent. Please verify.']);
    // }

    // public function requestLoginOtp(Request $request, OtpService $otpService)
    // {
    //     $request->validate([
    //         'login' => 'required|string',
    //     ]);

    //     $login = $request->login;

    //     $hourKey = "otp-hour:$login";
    //     $dayKey = "otp-day:$login";
    //     $hourLimit = 5;
    //     $dayLimit = 7;

    //     // Rate Limiting
    //     if (RateLimiter::tooManyAttempts($hourKey, $hourLimit)) {
    //         $seconds = RateLimiter::availableIn($hourKey);
    //         return response()->json([
    //             'message' => 'Too many attempts. Please try again after one hour.',
    //             'retry_after_seconds' => $seconds,
    //             'type' => 'hourly'
    //         ], 429);
    //     }

    //     if (RateLimiter::tooManyAttempts($dayKey, $dayLimit)) {
    //         $seconds = RateLimiter::availableIn($dayKey);
    //         return response()->json([
    //             'message' => 'Daily limit reached. Please try again after 24 hours.',
    //             'retry_after_seconds' => $seconds,
    //             'type' => 'daily'
    //         ], 429);
    //     }

    //     $user = User::where('email', $login)->orWhere('phone', $login)->first();

    //     if (!$user) {
    //         return response()->json(['message' => 'User not found.'], 404);
    //     }

    //     // Require email verification if login is via email
    //     if (filter_var($login, FILTER_VALIDATE_EMAIL) && !$user->hasVerifiedEmail()) {
    //         return response()->json([
    //             'message' => 'Your email is not verified. Please verify it before logging in.',
    //             'status' => 'unverified',
    //         ], 403);
    //     }

    //     // Hit Rate Limits
    //     RateLimiter::hit($hourKey, 3600);    // 1 hour
    //     RateLimiter::hit($dayKey, 86400);    // 24 hours

    //     // Determine OTP type
    //     $otpType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'user_phone';

    //     // Generate and send OTP
    //     $otpService->generateOtp($otpType, $login);

    //     return response()->json(['message' => 'OTP sent. Please verify.']);
    // }

    public function requestLoginOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'login' => 'required|string',
        ]);

        $login = $request->login;

        // Check rate limits
        $rateLimit = $otpService->checkRateLimit($login);
        if (!$rateLimit['allowed']) {
            return response()->json([
                'message' => $rateLimit['message'],
                'retry_after_seconds' => $rateLimit['retry_after_seconds'],
                'type' => $rateLimit['type'],
            ], $rateLimit['status']);
        }

        $user = User::where('email', $login)->orWhere('phone', $login)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if (filter_var($login, FILTER_VALIDATE_EMAIL) && !$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Your email is not verified. Please verify it before logging in.',
                'status' => 'unverified',
            ], 403);
        }

        $otpType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'user_phone';
        $otpService->generateOtp($otpType, $login);

        return response()->json(['message' => 'OTP sent. Please verify.']);
    }


    // public function verifyLoginOtp(Request $request)
    // {
    //     $request->validate([
    //         'login' => 'required|string',
    //         'otp' => 'required|string'
    //     ]);

    //     $user = User::where('email', $request->login)
    //                 ->orWhere('phone', $request->login)
    //                 ->first();

    //     if (!$user || $user->login_otp !== $request->otp) {
    //         return response()->json(['message' => 'Invalid OTP'], 401);
    //     }

    //     if (now()->greaterThan($user->otp_expires_at)) {
    //         return response()->json(['message' => 'OTP expired'], 403);
    //     }

    //     // Clear OTP
    //     $user->login_otp = null;
    //     $user->otp_expires_at = null;
    //     $user->save();

    //     // 🔥 Load the correct profile relationship
    //     if ($user->role === 'company') {
    //         $user->load('companyProfile');
    //         $profile = $user->companyProfile;
    //     } else {
    //         $user->load('individualProfile');
    //         $profile = $user->individualProfile;
    //     }

    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return response()->json([
    //         'access_token' => $token,
    //         'token_type' => 'Bearer',
    //         'user' => [
    //             'id' => $user->id,
    //             'email' => $user->email,
    //             'phone' => $user->phone,
    //             'role' => $user->role,
    //             'status' => $user->status,
    //             'profile' => $profile, // ✅ Now fully loaded
    //         ]
    //     ]);
    // }

    public function verifyLoginOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'login' => 'required|string',
            'otp' => 'required|string'
        ]);

        $login = $request->login;
        $otp = $request->otp;

        $user = User::where('email', $login)->orWhere('phone', $login)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $otpType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'user_phone';

        if (!$otpService->verifyOtp($otpType, $login, $otp)) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 401);
        }

        // ✅ Mark phone as verified if logging in via phone
        if ($otpType === 'user_phone' && !$user->is_phone_verified) {
            $user->is_phone_verified = true;
            $user->save();
        }

        // Load associated profile
        $user->load($user->role === 'company' ? 'companyProfile' : 'individualProfile');
        $profile = $user->role === 'company' ? $user->companyProfile : $user->individualProfile;

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_phone_verified' => $user->is_phone_verified,
                'role' => $user->role,
                'status' => $user->status,
                'profile' => $profile,
            ]
        ]);
    }

    public function resendEmailVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $email = $request->email;

        // Rate limiting key
        $key = 'resend-email-verification:' . $email;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => "Too many requests. Please try again in $seconds seconds."
            ], 429);
        }

        RateLimiter::hit($key, 60); // 5 attempts per 60 seconds

        $user = User::where('email', $email)->first();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.'
            ], 400);
        }

        event(new Registered($user)); // resend verification email

        return response()->json([
            'message' => 'Verification email resent successfully.'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'company') {
            $user->load('companyProfile');
            $profile = $user->companyProfile;
        } else {
            $user->load('individualProfile');
            $profile = $user->individualProfile;
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_phone_verified' => $user->is_phone_verified,
                'role' => $user->role,
                'status' => $user->status,
                'profile' => $profile,
            ]
        ]);
    }

}
