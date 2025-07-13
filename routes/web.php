<?php

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\StripeController;

// Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
//     $user = \App\Models\User::findOrFail($id);

//     if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
//         abort(403, 'Invalid or expired verification link.');
//     }

//     if ($user->hasVerifiedEmail()) {
//         // Email was already verified before
//         return response()->json(['message' => 'Email already verified.']);
//     }

//     $user->markEmailAsVerified();

//     return response()->json(['message' => 'Email verified successfully.']);
// })->middleware(['signed'])->name('verification.verify');


Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);

    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403, 'Invalid or expired verification link.');
    }

    if ($user->hasVerifiedEmail()) {
        return view('email_verified')->with('status', 'already_verified');
    }

    $user->markEmailAsVerified();

    return view('email_verified')->with('status', 'success');
})->middleware(['signed'])->name('verification.verify');

Route::middleware(['web'])->group(function () {
    Route::get('/payment-success', [StripeController::class, 'handleSuccess']);
});
