<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\AdImage;
use App\Models\AdDynamicField;
use App\Models\AdDynamicValue;
use App\Models\PendingAd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class AdController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'city' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'show_contact_number' => 'required|boolean',
            'dynamic_fields' => 'array',
            'dynamic_fields.*.field_id' => 'required|exists:ad_dynamic_fields,id',
            'dynamic_fields.*.value' => 'required|string',
            'images.*' => 'nullable|image|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // 1. Create the Ad
            $ad = Ad::create([
                'user_id' => auth()->id(),
                'user_type' => auth()->user()->role,
                'category_id' => $request->category_id,
                'subcategory_id' => $request->subcategory_id,
                'title' => $request->title,
                'description' => $request->description,
                'price' => $request->price,
                'city' => $request->city, // <-- Add this
                'address' => $request->address,
                'contact_number' => $request->contact_number,
                'show_contact_number' => $request->show_contact_number,
                'status' => 'approved',
            ]);

            // 2. Save Dynamic Field Values
            foreach ($request->dynamic_fields as $field) {
                AdDynamicValue::create([
                    'ad_id' => $ad->id,
                    'field_id' => $field['field_id'],
                    'value' => $field['value'],
                ]);
            }

            // 3. Upload and Save Images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('ads/images', 'public');
                    AdImage::create([
                        'ad_id' => $ad->id,
                        'image_path' => $path,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ad posted successfully!',
            ]);


        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Ad creation failed', 'message' => $e->getMessage()], 500);
        }
    }

    public function createPendingAd(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:subcategories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'city' => 'nullable|string',
            'address' => 'nullable|string',
            'contact_number' => 'nullable|string',
            'show_contact_number' => 'required|boolean',
            'featured_plan_id' => 'required|exists:featured_ad_plans,id',
            'dynamic_fields' => 'nullable|array',
            'images' => 'nullable|array',
            'images.*' => 'file|image|max:2048',
        ]);

        // ✅ Upload images temporarily (store file names)
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $imagePaths[] = $img->store('pending-ads', 'public');
            }
        }

        $pendingAd = PendingAd::create([
            'user_id' => $user->id,
            'category_id' => $validated['category_id'],
            'subcategory_id' => $validated['subcategory_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'show_contact_number' => $validated['show_contact_number'],
            'featured_plan_id' => $validated['featured_plan_id'],
            'dynamic_fields' => $validated['dynamic_fields'] ?? [],
            'images' => $imagePaths,
            'status' => 'approved',
        ]);

        return response()->json([
            'message' => 'Pending ad saved.',
            'pending_ad_id' => $pendingAd->id,
        ]);
    }

    public function myActiveAds(Request $request)
    {
        $ads = Ad::with(['images' => function ($query) {
                        $query->limit(1); // Only fetch first image
                    }])
                    ->where('user_id', $request->user()->id)
                    ->latest()
                    ->get()
                    ->transform(fn ($ad) => $ad->appendFirstImageUrl());

        return response()->json($ads);
    }

    public function myPendingAds(Request $request)
    {
        $ads = PendingAd::with(['images' => function ($query) {
                        $query->limit(1);
                    }])
                    ->where('user_id', $request->user()->id)
                    ->latest()
                    ->get()
                    ->transform(fn ($ad) => $ad->appendFirstImageUrl());

        return response()->json($ads);
    }

    public function show($id)
    {
        $ad = Ad::with([
            'images',
            'category:id,name',
            'subcategory:id,name',
            'dynamicValues.field'
        ])->findOrFail($id);

        // Add full URL to each image
        $ad->images->transform(function ($img) {
            $img->full_url = asset('storage/' . $img->image_path);
            return $img;
        });

        $adArray = $ad->toArray(); // now that full_url is added

        return response()->json([
            ...$adArray,
            'dynamic_fields' => $ad->dynamicValues,
        ]);
    }


    public function showPublic($id)
    {
        $ad = Ad::with([
            'images',
            'category:id,name',
            'subcategory:id,name',
            'dynamicValues.field',
            'user.individualProfile',  // ✅ Load individual profile
            'user.companyProfile'      // ✅ Load company profile
        ])->where('status', 'approved')->findOrFail($id);

        // Full image URLs
        $ad->images->transform(function ($img) {
            $img->full_url = asset('storage/' . $img->image_path);
            return $img;
        });

        // ✅ Calculate seller's active ads count
        $activeAdsCount = Ad::where('user_id', $ad->user_id)
            ->where('status', 'approved')
            ->count();

        // ✅ Build seller public info
        $user = $ad->user;
        $sellerName = '';
        $profilePic = null;

        if ($user->role === 'individual' && $user->individualProfile) {
            $sellerName = $user->individualProfile->first_name . ' ' . $user->individualProfile->last_name;
            $profilePic = $user->individualProfile->profile_picture
                ? asset('storage/' . $user->individualProfile->profile_picture)
                : null;
        }

        if ($user->role === 'company' && $user->companyProfile) {
            $sellerName = $user->companyProfile->company_name;
            $profilePic = $user->companyProfile->logo
                ? asset('storage/' . $user->companyProfile->logo)
                : null;
        }

        $adArray = $ad->toArray();

        return response()->json([
            ...$adArray,
            'dynamic_fields' => $ad->dynamicValues,
            'user' => [
                'id' => $user->id,
                'role' => $user->role,
                'name' => $sellerName,
                'profile_picture' => $profilePic,
                'created_at' => $user->created_at,
                'active_ads_count' => $activeAdsCount,
            ],
        ]);
    }


    public function destroy(Request $request, $id)
    {
        $ad = Ad::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (!$ad) {
            return response()->json(['message' => 'Ad not found or unauthorized.'], 404);
        }

        // Delete related images and dynamic values
        $ad->images()->delete();
        $ad->dynamicValues()->delete();
        $ad->delete();

        return response()->json(['message' => 'Ad deleted successfully.']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'city' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            // contact_number is not editable
            'show_contact_number' => 'required|boolean',
            'dynamic_fields' => 'array',
            'dynamic_fields.*.field_id' => 'required|exists:ad_dynamic_fields,id',
            'dynamic_fields.*.value' => 'required|string',
            'images.*' => 'nullable|image|max:2048',
            'remove_image_ids' => 'array',
            'remove_image_ids.*' => 'exists:ad_images,id'
        ]);

        DB::beginTransaction();

        try {
            $ad = Ad::where('user_id', auth()->id())->findOrFail($id);

            $ad->update([
                'category_id' => $request->category_id,
                'subcategory_id' => $request->subcategory_id,
                'title' => $request->title,
                'description' => $request->description,
                'price' => $request->price,
                'city' => $request->city,
                'address' => $request->address,
                'show_contact_number' => $request->show_contact_number,
            ]);

            // Update dynamic fields
            foreach ($request->dynamic_fields as $field) {
                AdDynamicValue::updateOrCreate(
                    ['ad_id' => $ad->id, 'field_id' => $field['field_id']],
                    ['value' => $field['value']]
                );
            }

            // Remove selected images
            if ($request->has('remove_image_ids')) {
                foreach ($request->remove_image_ids as $imgId) {
                    $img = AdImage::where('ad_id', $ad->id)->find($imgId);
                    if ($img) {
                        Storage::disk('public')->delete($img->image_path);
                        $img->delete();
                    }
                }
            }

            // Upload new images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('ads/images', 'public');
                    AdImage::create([
                        'ad_id' => $ad->id,
                        'image_path' => $path,
                    ]);
                }
            }

            DB::commit();

            return response()->json(['message' => 'Ad updated successfully', 'ad' => $ad], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Update failed', 'message' => $e->getMessage()], 500);
        }
    }

    public function featuredAds()
    {
        $ads = Ad::with('images')
            ->where('is_featured', true)
            ->where('status', 'approved')
            ->latest()
            ->take(8)
            ->get()
            ->transform(fn ($ad) => $ad->appendFirstImageUrl());

        return response()->json($ads);
    }

    public function adsByCategory($categoryId)
    {
        $ads = Ad::with('images')  // ✅ Load images relation
            ->where('category_id', $categoryId)
            ->where('status', 'approved')
            ->latest()
            ->take(8)
            ->get()
            ->transform(fn ($ad) => $ad->appendFirstImageUrl());

        return response()->json($ads);
    }

    public function adsBySubcategory($subcategoryId)
    {
        $ads = Ad::with('images')  // ✅ Load images relation
            ->where('subcategory_id', $subcategoryId)
            ->where('status', 'approved')
            ->latest()
            ->take(8)
            ->get()
             ->transform(fn ($ad) => $ad->appendFirstImageUrl());

        return response()->json($ads);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,sold',
        ]);

        $ad = Ad::where('user_id', auth()->id())->findOrFail($id); // only allow owner
        $ad->status = $request->status;
        $ad->save();

        return response()->json(['message' => 'Ad status updated successfully.']);
    }

    public function search(Request $request)
    {
        $query = Ad::query()
            ->where('status', 'approved')
            ->withCount('images'); // For images count ordering

        $keyword = trim($request->keyword);
        $city = $request->city;
        $subcategoryId = $request->subcategory_id;
        $clickedAdId = $request->clicked_ad_id; // (Optional, for suggestion click boost)

        // Optional eager load relationships (uncomment if needed)
        // ->with(['category', 'subcategory', 'images']);

        // Step 1: Filter by City & Subcategory
        if ($city) {
            $query->where('city', $city);
        }

        if ($subcategoryId) {
            $query->where('subcategory_id', $subcategoryId);
        }

        // Step 2: Keyword Search (Title, Description, Dynamic Fields)
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%")
                ->orWhereHas('dynamicValues', function ($q2) use ($keyword) {
                    $q2->where('value', 'like', "%{$keyword}%");
                });
            });
        }

        // Step 3: Custom Ordering Logic
        $orderParts = [];

        if ($clickedAdId) {
            $orderParts[] = "FIELD(id, ?) DESC";
        }

        if ($keyword) {
            $orderParts[] = "
                CASE 
                    WHEN title = ? THEN 1
                    WHEN title LIKE ? THEN 2
                    WHEN description LIKE ? THEN 3
                    ELSE 4
                END
            ";
        }

        // Featured ads boost
        $orderParts[] = "is_featured DESC";

        // Ads with images boost
        $orderParts[] = "CASE WHEN images_count > 0 THEN 1 ELSE 2 END";

        // Newest first (tie breaker)
        $orderParts[] = "created_at DESC";

        if (!empty($orderParts)) {
            $bindings = [];

            if ($clickedAdId) {
                $bindings[] = $clickedAdId;
            }

            if ($keyword) {
                $bindings[] = $keyword;
                $bindings[] = "{$keyword}%";
                $bindings[] = "%{$keyword}%";
            }

            $query->orderByRaw(implode(', ', $orderParts), $bindings);
        }

        return response()->json($query->paginate(10));
    }



    // public function searchSuggestions(Request $request)
    // {
    //     $keyword = trim($request->get('keyword'));
    //     $city = $request->get('city');
    //     $subcategoryId = $request->get('subcategory_id');

    //     // Ignore too-short keywords
    //     if (!$keyword || mb_strlen($keyword) < 2) {
    //         return response()->json([]);
    //     }

    //     $query = Ad::query()
    //         ->where('status', 'approved')
    //         ->withCount('images');

    //     // Apply Filters (City + Subcategory)
    //     if ($city) {
    //         $query->where('city', $city);
    //     }

    //     if ($subcategoryId) {
    //         $query->where('subcategory_id', $subcategoryId);
    //     }

    //     // Match Logic: Title, Description, Dynamic Field Values
    //     $query->where(function ($q) use ($keyword) {
    //         $q->where('title', 'like', "%{$keyword}%")
    //         ->orWhere('description', 'like', "%{$keyword}%")
    //         ->orWhereHas('dynamicValues', function ($q2) use ($keyword) {
    //             $q2->where('value', 'like', "%{$keyword}%");
    //         });
    //     });

    //     // Custom Ordering: Featured → Exact → Starts with → Contains → Description → Dynamic Fields → Images → Newest
    //     $orderRaw = "
    //         is_featured DESC,
    //         CASE
    //             WHEN title = ? THEN 1
    //             WHEN title LIKE ? THEN 2
    //             WHEN title LIKE ? THEN 3
    //             WHEN description LIKE ? THEN 4
    //             ELSE 5
    //         END,
    //         images_count DESC,
    //         created_at DESC
    //     ";

    //     $bindings = [
    //         $keyword,
    //         "{$keyword}%",
    //         "%{$keyword}%",
    //         "%{$keyword}%",
    //     ];

    //     $suggestions = $query
    //         ->orderByRaw($orderRaw, $bindings)
    //         ->limit(8)
    //         ->get(['id', 'title']);

    //     return response()->json($suggestions);
    // }


    // public function searchSuggestions(Request $request)
    // {
    //     $keyword = trim($request->get('keyword'));
    //     $city = $request->get('city');
    //     $subcategoryId = $request->get('subcategory_id');

    //     // Prevent queries for too short keywords
    //     if (!$keyword || mb_strlen($keyword) < 2) {
    //         return response()->json([]);
    //     }

    //     $query = Ad::query()
    //         ->where('status', 'approved')
    //         ->withCount('images'); // For image count ordering

    //     // Optional City Filter
    //     if ($city) {
    //         $query->where('city', $city);
    //     }

    //     // Optional Subcategory Filter
    //     if ($subcategoryId) {
    //         $query->where('subcategory_id', $subcategoryId);
    //     }

    //     // Match Logic: Title, Description, Dynamic Fields
    //     $query->where(function ($q) use ($keyword) {
    //         $q->where('title', 'like', "%{$keyword}%")
    //         ->orWhere('description', 'like', "%{$keyword}%")
    //         ->orWhereHas('dynamicValues', function ($q2) use ($keyword) {
    //             $q2->where('value', 'like', "%{$keyword}%");
    //         });
    //     });

    //     // Ranking Logic
    //     $orderRaw = "
    //         is_featured DESC,
    //         CASE
    //             WHEN title = ? THEN 1
    //             WHEN title LIKE ? THEN 2
    //             WHEN title LIKE ? THEN 3
    //             WHEN description LIKE ? THEN 4
    //             ELSE 5
    //         END,
    //         images_count DESC,
    //         created_at DESC
    //     ";

    //     $bindings = [
    //         $keyword,
    //         "{$keyword}%",
    //         "%{$keyword}%",
    //         "%{$keyword}%",
    //     ];

    //     $query->orderByRaw($orderRaw, $bindings);

    //     // Limit number of suggestions
    //     $suggestions = $query->limit(8)->get(['id', 'title']);

    //     return response()->json($suggestions);
    // }

    // public function searchSuggestions(Request $request)
    // {
    //     $keyword = trim($request->get('keyword'));
    //     $city = $request->get('city');
    //     $subcategoryId = $request->get('subcategory_id');

    //     if (!$keyword || mb_strlen($keyword) < 2) {
    //         return response()->json([]);
    //     }

    //     // Split the keyword into words for individual matching
    //     $keywords = preg_split('/\s+/', $keyword);

    //     $query = Ad::query()
    //         ->where('status', 'approved')
    //         ->withCount('images')
    //         ->with(['category', 'subcategory']);

    //     // Optional City Filter
    //     if ($city) {
    //         $query->where('city', $city);
    //     }

    //     // Optional Subcategory Filter
    //     if ($subcategoryId) {
    //         $query->where('subcategory_id', $subcategoryId);
    //     }

    //     // Allow ads that match any keyword (OR), but track per-ad match count
    //     $query->where(function ($q) use ($keywords) {
    //         foreach ($keywords as $word) {
    //             $q->orWhere(function ($subQ) use ($word) {
    //                 $subQ->where('title', 'like', "%{$word}%")
    //                     ->orWhere('description', 'like', "%{$word}%")
    //                     ->orWhereHas('dynamicValues', function ($q2) use ($word) {
    //                         $q2->where('value', 'like', "%{$word}%");
    //                     })
    //                     ->orWhereHas('subcategory', function ($q3) use ($word) {
    //                         $q3->where('name', 'like', "%{$word}%")
    //                             ->orWhere('slug', 'like', "%{$word}%");
    //                     })
    //                     ->orWhereHas('category', function ($q4) use ($word) {
    //                         $q4->where('name', 'like', "%{$word}%")
    //                             ->orWhere('slug', 'like', "%{$word}%");
    //                     });
    //             });
    //         }
    //     });

    //     // Calculate match count per ad (how many words each ad matches)
    //     $matchCountSqlParts = [];
    //     $bindings = [];

    //     foreach ($keywords as $word) {
    //         $likeWord = "%{$word}%";
    //         $matchCountSqlParts[] = "
    //             (CASE
    //                 WHEN ads.title LIKE ? THEN 1
    //                 WHEN ads.description LIKE ? THEN 1
    //                 WHEN subcategories.name LIKE ? THEN 1
    //                 WHEN categories.name LIKE ? THEN 1
    //                 ELSE 0
    //             END)
    //         ";
    //         $bindings[] = $likeWord;
    //         $bindings[] = $likeWord;
    //         $bindings[] = $likeWord;
    //         $bindings[] = $likeWord;
    //     }

    //     $matchCountSql = implode(' + ', $matchCountSqlParts);

    //     $orderRaw = "
    //         is_featured DESC,
    //         CASE
    //             WHEN ads.title = ? THEN 1
    //             WHEN ads.title LIKE ? THEN 2
    //             WHEN ads.title LIKE ? THEN 3
    //             WHEN ads.description LIKE ? THEN 4
    //             ELSE 5
    //         END,
    //         ({$matchCountSql}) DESC,
    //         images_count DESC,
    //         ads.created_at DESC
    //     ";

    //     // Bindings for CASE + match count
    //     $orderBindings = [
    //         $keyword,
    //         "{$keyword}%",
    //         "%{$keyword}%",
    //         "%{$keyword}%",
    //     ];
    //     $orderBindings = array_merge($orderBindings, $bindings);

    //     // Join needed for subcategory and category fields in ordering
    //     $query->leftJoin('subcategories', 'ads.subcategory_id', '=', 'subcategories.id')
    //           ->leftJoin('categories', 'subcategories.category_id', '=', 'categories.id');

    //     $query->orderByRaw($orderRaw, $orderBindings);

    //     $suggestions = $query->limit(8)->get(['ads.id', 'ads.title']);

    //     return response()->json($suggestions);
    // }


    public function searchSuggestions(Request $request)
    {
        $keyword = trim($request->get('keyword'));

        if (!$keyword || mb_strlen($keyword) < 2) {
            return response()->json([]);
        }

        $suggestions = Ad::search($keyword, function ($meilisearch, $query, $options) use ($request) {
        $filters = [];

        if ($request->get('city')) {
            $filters[] = 'city = "' . $request->get('city') . '"';
        }

        if ($request->get('subcategory_id')) {
            $filters[] = 'subcategory_id = ' . intval($request->get('subcategory_id'));
        }

        if (!empty($filters)) {
            $options['filter'] = implode(' AND ', $filters);
        }

        return $meilisearch->search($query, $options);
        })->take(8)->get(['id', 'title']);

    }











}
