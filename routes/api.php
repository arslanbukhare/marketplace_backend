<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\SearchController;
use App\Models\Category;

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login/request-otp', [AuthController::class, 'requestLoginOtp']);
Route::post('/login/verify-otp', [AuthController::class, 'verifyLoginOtp']);
Route::post('/email/resend-verification', [AuthController::class, 'resendEmailVerification']);

    Route::get('/ads/category/{id}', [AdController::class, 'adsByCategory']);
    Route::get('/ads/subcategory/{id}', [AdController::class, 'adsBySubcategory']);
    Route::get('/ads/featured', [AdController::class, 'featuredAds']);
    Route::get('/public-ads/{id}', [AdController::class, 'showPublic']);
    Route::get('/ads', [AdController::class, 'search']);
    Route::get('/search-suggestions', [AdController::class, 'searchSuggestions']);
    Route::get('/categories/{id}/filters', [SearchController::class, 'categoryFilters']);



Route::get('/search-suggestions', [SearchController::class, 'searchSuggestions']);
Route::get('/search-results', [SearchController::class, 'searchResults']);
Route::get('/setup-meili-filters', [SearchController::class, 'setupMeilisearchFilters']); // Run this once and remove



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

    Route::post('/post-ad', [AdController::class, 'store']);
    
    Route::get('/categories', function () {
        return \App\Models\Category::select('id', 'name', 'icon')->get()->map(function ($cat) {
            $cat->icon_url = $cat->icon ? Storage::url($cat->icon) : null;
            return $cat;
        });
    });

    Route::get('/categories/{id}/subcategories', function ($id) {
    return \App\Models\Subcategory::where('category_id', $id)->get();
    });

    Route::get('/subcategories', function () {
    return \App\Models\Subcategory::select('id', 'name')->get();
    });


    Route::get('/categories/{id}/fields', function ($id) {
    return \App\Models\AdDynamicField::with('options')
        ->where('category_id', $id)
        ->get();
    });

    Route::get('/featured-plans', function () {
    return \App\Models\FeaturedAdPlan::all();
    });

    Route::post('/create-checkout-session', [StripeController::class, 'createCheckoutSession']);
    Route::post('/pending-ad', [AdController::class, 'createPendingAd']);

    Route::get('/my-ads/active', [AdController::class, 'myActiveAds']);
    Route::get('/my-ads/pending', [AdController::class, 'myPendingAds']);
    Route::get('/ads/{id}', [AdController::class, 'show']);
    Route::delete('/ads/{id}', [AdController::class, 'destroy']);
    Route::put('/ads/{id}', [AdController::class, 'update']);
    Route::patch('/ads/{id}/status', [AdController::class, 'updateStatus']);

});

