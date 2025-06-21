<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\OtpService;

class ProfileController extends Controller
{
    // -----------------------
    // INDIVIDUAL PROFILE
    // -----------------------

    public function updateIndividualBasicInfo(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'individual') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'profile_picture' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $user->individualProfile->profile_picture = $path;
        }

        $user->individualProfile->update($request->only([
            'first_name', 'last_name', 'gender', 'dob'
        ]));

        $user->individualProfile->save();

        return response()->json(['message' => 'Individual basic info updated successfully.']);
    }

    public function updateIndividualAddress(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'individual') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $user->individualProfile->update($request->only([
            'address', 'city', 'state', 'country', 'postal_code'
        ]));

        return response()->json(['message' => 'Individual address updated successfully.']);
    }

    public function updateIndividualProfilePicture(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'individual') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'profile_picture' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $user->individualProfile->update(['profile_picture' => $path]);
        }

        return response()->json(['message' => 'Profile picture updated successfully.']);
    }

    // -----------------------
    // COMPANY PROFILE
    // -----------------------

    public function updateCompanyBasicInfo(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'company_name' => 'required|string|max:255',
            'industry' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $user->companyProfile->update($request->only([
            'company_name', 'industry', 'description'
        ]));

        return response()->json(['message' => 'Company basic info updated successfully.']);
    }

     public function updateCompanyAddress(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $user->CompanyProfile->update($request->only([
            'address', 'city', 'state', 'country', 'postal_code'
        ]));

        return response()->json(['message' => 'Company address updated successfully.']);
    }

    public function updateCompanyTradeLicense(Request $request)
    {
        \Log::info('Trade License Form Submission:', $request->all());
        $user = $request->user();

        if ($user->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'registration_number' => 'nullable|string|max:255',
            'registration_expiry_date' => 'nullable|date',
            'registration_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $profile = $user->companyProfile;

        // Only prevent update if already in pending state
        if ($profile->registration_document_status === 'pending') {
            return response()->json(['message' => 'Verification already in progress.'], 403);
        }

        $updateData = $request->only(['registration_number', 'registration_expiry_date']);
        $updateData['registration_document_status'] = 'pending';

        if ($request->hasFile('registration_document')) {
            if ($profile->registration_document) {
                Storage::disk('public')->delete($profile->registration_document);
            }

            $path = $request->file('registration_document')->store('registration_documents', 'public');
            $updateData['registration_document'] = $path;
        }
        $profile->update($updateData);

        return response()->json([
            'message' => 'Trade license verification requested successfully.',
        ]);
    }




    public function updateCompanyWebsiteAndContact(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'website' => 'nullable|url',
        ]);

        $user->companyProfile->update($request->only(['website']));

        return response()->json(['message' => 'Company website & contact info updated successfully.']);
    }


    public function updateCompanyLogo(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'company') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('company_logos', 'public');
            $user->companyProfile->update(['logo' => $path]);
        }

        return response()->json(['message' => 'Company logo updated successfully.']);
    }

    // public function requestPhoneOtp(Request $request, OtpService $otpService)
    // {
    //     $request->validate([
    //         'phone' => [
    //             'required',
    //             'string',
    //             'regex:/^(05\d{8}|\+9715\d{8})$/'
    //         ],
    //         'type' => 'required|in:phone,contact_phone',
    //     ]);

    //     $user = auth()->user();
    //     $phone = $request->phone;
    //     $type = $request->type;

    //     // Check if already verified
    //     if ($type === 'phone' && $user->is_phone_verified) {
    //         return response()->json(['message' => 'Phone number is already verified.'], 403);
    //     }

    //     if ($type === 'contact_phone') {
    //         if (!$user->companyProfile) {
    //             return response()->json(['message' => 'Company profile not found.'], 404);
    //         }

    //         if ($user->companyProfile->is_contact_phone_verified) {
    //             return response()->json(['message' => 'Contact phone is already verified.'], 403);
    //         }
    //     }

    //     // Apply rate limiting
    //     $rateLimit = $otpService->checkRateLimit($phone);
    //     if (!$rateLimit['allowed']) {
    //         return response()->json([
    //             'message' => $rateLimit['message'],
    //             'retry_after_seconds' => $rateLimit['retry_after_seconds'],
    //             'type' => $rateLimit['type'],
    //         ], $rateLimit['status']);
    //     }

    //     $otpService->generateOtp($type, $phone);

    //     return response()->json(['message' => 'OTP sent. Please verify.']);
    // }

    public function requestPhoneOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'phone' => [
                'required',
                'string',
                'regex:/^(05\d{8}|\+9715\d{8})$/'
            ],
            'type' => 'required|in:phone,contact_phone',
        ]);

        $user = auth()->user();
        $phone = $request->phone;
        $type = $request->type;

        // Check if already verified
        if ($type === 'phone' && $user->is_phone_verified) {
            return response()->json(['message' => 'Phone number is already verified.'], 403);
        }

        if ($type === 'contact_phone') {
            $company = $user->companyProfile;

            if (!$company) {
                return response()->json(['message' => 'Company profile not found.'], 404);
            }

            if ($company->is_contact_phone_verified) {
                return response()->json(['message' => 'Contact phone is already verified.'], 403);
            }

            // ✅ Auto-verify contact_phone if same as already verified login phone
            if ($phone === $user->phone && $user->is_phone_verified) {
                $company->update([
                    'contact_phone' => $phone,
                    'is_contact_phone_verified' => true,
                ]);

                return response()->json([
                    'message' => 'Contact phone auto-verified (same as login phone).',
                    'auto_verified' => true,
                ], 200);
            }
        }

        // Apply rate limiting
        $rateLimit = $otpService->checkRateLimit($phone);
        if (!$rateLimit['allowed']) {
            return response()->json([
                'message' => $rateLimit['message'],
                'retry_after_seconds' => $rateLimit['retry_after_seconds'],
                'type' => $rateLimit['type'],
            ], $rateLimit['status']);
        }

        $otpService->generateOtp($type, $phone);

        return response()->json(['message' => 'OTP sent. Please verify.']);
    }



    public function verifyPhoneOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'phone' => [
                'required',
                'string',
                'regex:/^(05\d{8}|\+9715\d{8})$/'
            ],
            'type' => 'required|in:phone,contact_phone',
            'otp' => 'required|string'
        ]);

        $user = auth()->user();

        if (!$otpService->verifyOtp($request->type, $request->phone, $request->otp)) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 401);
        }

        if ($request->type === 'phone') {
            $user->update([
                'phone' => $request->phone,
                'is_phone_verified' => true
            ]);
        }

        if ($request->type === 'contact_phone') {
            $company = $user->companyProfile;

            if (!$company) {
                return response()->json(['message' => 'Company profile not found.'], 404);
            }

            $company->update([
                'contact_phone' => $request->phone,
                'is_contact_phone_verified' => true
            ]);
        }

        return response()->json(['message' => 'Phone verified successfully.']);
    }


}
