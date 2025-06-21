<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login/request-otp', [AuthController::class, 'requestLoginOtp']);
Route::post('/login/verify-otp', [AuthController::class, 'verifyLoginOtp']);
Route::post('/email/resend-verification', [AuthController::class, 'resendEmailVerification']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // ✅ New route to get current authenticated user
    Route::get('/user', [AuthController::class, 'me']);

    // Individual profile update routes
    Route::post('/profile/individual/basic-info', [ProfileController::class, 'updateIndividualBasicInfo']);
    Route::post('/profile/individual/address', [ProfileController::class, 'updateIndividualAddress']);
    Route::put('/profile/individual/profile-picture', [ProfileController::class, 'updateIndividualProfilePicture']);

    // Company profile update routes
    Route::post('/profile/company/basic-info', [ProfileController::class, 'updateCompanyBasicInfo']);
    Route::post('/profile/company/address', [ProfileController::class, 'updateCompanyAddress']);
    Route::post('/profile/company/trade-license', [ProfileController::class, 'updateCompanyTradeLicense']);
    Route::post('/profile/company/contact-info', [ProfileController::class, 'updateCompanyWebsiteAndContact']);
    Route::post('/profile/company/logo', [ProfileController::class, 'updateCompanyLogo']);
    Route::post('/profile/request-phone-otp', [ProfileController::class, 'requestPhoneOtp']);
    Route::post('/profile/verify-phone-otp', [ProfileController::class, 'verifyPhoneOtp']);

    
});
