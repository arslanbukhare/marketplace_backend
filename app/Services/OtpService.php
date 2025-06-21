<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

// class OtpService
// {
//     protected $ttl = 300; // 5 minutes in seconds

//     public function generateOtp(string $type, string $identifier): string
//     {
//         $otp = rand(100000, 999999);
//         $key = $this->getOtpKey($type, $identifier);

//         Cache::put($key, $otp, $this->ttl);

//         // Log or dispatch OTP sending event
//         logger("OTP generated for {$type} - {$identifier}: {$otp}");

//         return $otp;
//     }

//     public function verifyOtp(string $type, string $identifier, string $otp): bool
//     {
//         $key = $this->getOtpKey($type, $identifier);
//         $storedOtp = Cache::get($key);

//         if ($storedOtp && $storedOtp == $otp) {
//             Cache::forget($key); // Use once
//             return true;
//         }

//         return false;
//     }

//     protected function getOtpKey(string $type, string $identifier): string
//     {
//         return 'otp:' . $type . ':' . Str::lower($identifier);
//     }
// }

class OtpService
{
    protected $ttl = 300; // 5 minutes

    public function generateOtp(string $type, string $identifier): string
    {
        $otp = rand(100000, 999999);
        $key = $this->getOtpKey($type, $identifier);

        Cache::put($key, $otp, $this->ttl);

        // Log or dispatch OTP sending event
        logger("OTP generated for {$type} - {$identifier}: {$otp}");

        return $otp;
    }

    public function verifyOtp(string $type, string $identifier, string $otp): bool
    {
        $key = $this->getOtpKey($type, $identifier);
        $storedOtp = Cache::get($key);

        if ($storedOtp && $storedOtp == $otp) {
            Cache::forget($key); // OTP can be used only once
            return true;
        }

        return false;
    }

    public function checkRateLimit(string $identifier, int $hourLimit = 5, int $dayLimit = 7): array
    {
        $hourKey = "otp-hour:" . Str::lower($identifier);
        $dayKey = "otp-day:" . Str::lower($identifier);

        if (RateLimiter::tooManyAttempts($hourKey, $hourLimit)) {
            $seconds = RateLimiter::availableIn($hourKey);
            return [
                'allowed' => false,
                'message' => 'Too many attempts. Please try again after one hour.',
                'retry_after_seconds' => $seconds,
                'type' => 'hourly',
                'status' => 429,
            ];
        }

        if (RateLimiter::tooManyAttempts($dayKey, $dayLimit)) {
            $seconds = RateLimiter::availableIn($dayKey);
            return [
                'allowed' => false,
                'message' => 'Daily limit reached. Please try again after 24 hours.',
                'retry_after_seconds' => $seconds,
                'type' => 'daily',
                'status' => 429,
            ];
        }

        // If allowed, register the attempt
        RateLimiter::hit($hourKey, 3600);   // 1 hour
        RateLimiter::hit($dayKey, 86400);   // 24 hours

        return ['allowed' => true];
    }

    protected function getOtpKey(string $type, string $identifier): string
    {
        return 'otp:' . $type . ':' . Str::lower($identifier);
    }
}
