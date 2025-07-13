<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use App\Models\FeaturedAdPlan;
use App\Models\Ad;
use App\Models\User;
use App\Models\AdImage;
use App\Models\AdDynamicValue;
use App\Models\Payment;
use App\Models\PendingAd;



class StripeController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'featured_plan_id' => 'required|exists:featured_ad_plans,id',
            'pending_ad_id' => 'required|exists:pending_ads,id',
        ]);

        $plan = FeaturedAdPlan::findOrFail($request->featured_plan_id);
        $pendingAdId = $request->pending_ad_id;
        $userId = $request->user()->id;

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($plan->currency),
                    'unit_amount' => intval($plan->price * 100),
                    'product_data' => [
                        'name' => $plan->name,
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => url('/payment-success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url('/payment-cancelled'),
            'metadata' => [
                'user_id' => $userId,
                'featured_plan_id' => $plan->id,
                'pending_ad_id' => $pendingAdId,
            ],
        ]);

        return response()->json([
            'id' => $session->id,
            'url' => $session->url,
        ]);
    }

    // public function handleSuccess(Request $request)
    // {
    //     $sessionId = $request->query('session_id');

    //     if (!$sessionId) {
    //         return redirect('/error')->with('message', 'Missing session ID');
    //     }

    //     \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

    //     try {
    //         $session = \Stripe\Checkout\Session::retrieve($sessionId);
    //         $metadata = $session->metadata;

    //         $userId = $metadata['user_id'] ?? null;
    //         $planId = $metadata['featured_plan_id'] ?? null;
    //         $pendingAdId = $metadata['pending_ad_id'] ?? null;

    //         if (!$userId || !$planId || !$pendingAdId) {
    //             return redirect('/error')->with('message', 'Invalid metadata');
    //         }

    //         $pendingAd = PendingAd::where('id', $pendingAdId)->where('user_id', $userId)->firstOrFail();
    //         $plan = FeaturedAdPlan::findOrFail($planId);

    //         // ✅ Create the real ad
    //         $ad = Ad::create([
    //             'user_id' => $userId,
    //             'category_id' => $pendingAd->category_id,
    //             'subcategory_id' => $pendingAd->subcategory_id,
    //             'title' => $pendingAd->title,
    //             'description' => $pendingAd->description,
    //             'price' => $pendingAd->price,
    //             'city' => $pendingAd->city,
    //             'address' => $pendingAd->address,
    //             'contact_number' => $pendingAd->contact_number,
    //             'show_contact_number' => $pendingAd->show_contact_number,
    //             'is_featured' => true,
    //             'featured_expires_at' => now()->addDays($plan->duration_days),
    //             'status' => 'approved',
    //         ]);

    //         // ✅ Save dynamic fields
    //         if (!empty($pendingAd->dynamic_fields)) {
    //             foreach ($pendingAd->dynamic_fields as $field) {
    //                 AdDynamicValue::create([
    //                     'ad_id' => $ad->id,
    //                     'field_id' => $field['field_id'],
    //                     'value' => $field['value'],
    //                 ]);
    //             }
    //         }

    //         // ✅ Save images
    //         if (!empty($pendingAd->images)) {
    //             foreach ($pendingAd->images as $imgPath) {
    //                 AdImage::create([
    //                     'ad_id' => $ad->id,
    //                     'image_path' => $imgPath,
    //                 ]);
    //             }
    //         }

    //         // ✅ Log the payment
    //         Payment::create([
    //             'user_id' => $userId,
    //             'ad_id' => $ad->id,
    //             'amount' => $plan->price,
    //             'currency' => $plan->currency,
    //             'payment_method' => 'stripe',
    //             'payment_status' => 'completed',
    //             'paid_at' => now(),
    //             'stripe_session_id' => $session->id,
    //             'expires_at' => $ad->featured_expires_at,
    //         ]);

    //         // ✅ Cleanup: delete or mark pending ad
    //         $pendingAd->delete();

    //         return redirect(env('FRONTEND_URL') . '/ad-success?success=1&message=' . urlencode('Payment complete. Your ad is now live!'));
    //     } catch (\Exception $e) {
    //         \Log::error('Stripe payment success error: ' . $e->getMessage());
    //         return redirect('/error')->with('message', 'Something went wrong verifying payment.');
    //     }
    // }

    public function handleSuccess(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect('/error')->with('message', 'Missing session ID');
        }

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $session = \Stripe\Checkout\Session::retrieve($sessionId);
            $metadata = $session->metadata;

            $userId = $metadata['user_id'] ?? null;
            $planId = $metadata['featured_plan_id'] ?? null;
            $pendingAdId = $metadata['pending_ad_id'] ?? null;

            if (!$userId || !$planId || !$pendingAdId) {
                return redirect('/error')->with('message', 'Invalid metadata');
            }

            $pendingAd = PendingAd::where('id', $pendingAdId)->where('user_id', $userId)->firstOrFail();
            $plan = FeaturedAdPlan::findOrFail($planId);

            // ✅ Create the real ad
            $ad = Ad::create([
                'user_id' => $userId,
                'user_type' => User::find($userId)->role,
                'category_id' => $pendingAd->category_id,
                'subcategory_id' => $pendingAd->subcategory_id,
                'title' => $pendingAd->title,
                'description' => $pendingAd->description,
                'price' => $pendingAd->price,
                'city' => $pendingAd->city,
                'address' => $pendingAd->address,
                'contact_number' => $pendingAd->contact_number,
                'show_contact_number' => $pendingAd->show_contact_number,
                'is_featured' => true,
                'featured_expires_at' => now()->addDays($plan->duration_days),
                'status' => 'approved',
            ]);

            // ✅ Save dynamic fields
            if (!empty($pendingAd->dynamic_fields)) {
                foreach ($pendingAd->dynamic_fields as $field) {
                    AdDynamicValue::create([
                        'ad_id' => $ad->id,
                        'field_id' => $field['field_id'],
                        'value' => $field['value'],
                    ]);
                }
            }

            // ✅ Move images to the same folder as free ads (ads/images)
            if (!empty($pendingAd->images)) {
                foreach ($pendingAd->images as $imgPath) {
                    $filename = basename($imgPath);
                    $newPath = 'ads/images/' . $filename;

                    if (Storage::disk('public')->exists($imgPath)) {
                        Storage::disk('public')->move($imgPath, $newPath);
                    }

                    AdImage::create([
                        'ad_id' => $ad->id,
                        'image_path' => $newPath,
                    ]);
                }
            }

            // ✅ Log the payment
            Payment::create([
                'user_id' => $userId,
                'ad_id' => $ad->id,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'payment_method' => 'stripe',
                'payment_status' => 'completed',
                'paid_at' => now(),
                'stripe_session_id' => $session->id,
                'expires_at' => $ad->featured_expires_at,
            ]);

            // ✅ Delete the pending ad
            $pendingAd->delete();

            return redirect(env('FRONTEND_URL') . '/ad-success?success=1&message=' . urlencode('Payment complete. Your ad is now live!'));
        } catch (\Exception $e) {
            \Log::error('Stripe payment success error: ' . $e->getMessage());
            return redirect('/error')->with('message', 'Something went wrong verifying payment.');
        }
    }


}
