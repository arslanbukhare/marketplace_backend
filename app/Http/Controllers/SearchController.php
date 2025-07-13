<?php

namespace App\Http\Controllers;
use App\Models\Ad;
use App\Models\Category;
use Meilisearch\Endpoints\Indexes;

use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function searchSuggestions(Request $request)
    {
        $keyword = trim($request->get('keyword'));

        if (!$keyword || mb_strlen($keyword) < 2) {
            return response()->json([]);
        }

        $suggestions = Ad::search($keyword)
            ->take(8)
            ->get(['id', 'title', 'category_id']);

        return response()->json($suggestions);
    }

    /**
     * Full search results after clicking a suggestion or submitting a keyword.
     */

    // public function searchResults(Request $request)
    // {
    //     $keyword = trim($request->get('keyword'));
    //     $city = $request->get('city');
    //     $categoryId = $request->get('category_id');

    //     $query = Ad::search($keyword);

    //     if ($city) {
    //         $query->where('city', $city);
    //     }

    //     if ($categoryId) {
    //         $query->where('category_id', (int) $categoryId);
    //     }

    //     $ads = $query->paginate(20);

    //     return response()->json([
    //         'total' => $ads->total(),
    //         'ads' => $ads->items(),
    //     ]);
    // }


    // public function searchResults(Request $request)
    // {
    //     $keyword = trim($request->get('keyword'));
    //     $city = $request->get('city');
    //     $categoryId = $request->get('category_id');
    //     $dynamicFilters = $request->get('filters', []);

    //     $filterConditions = [];

    //     if ($city) {
    //         $filterConditions[] = 'city = "' . $city . '"';
    //     }

    //     if ($categoryId) {
    //         $filterConditions[] = 'category_id = ' . (int) $categoryId;
    //     }

    //     foreach ($dynamicFilters as $fieldId => $value) {
    //         $filterConditions[] = 'dynamic_values = "' . addslashes($value) . '"';
    //     }

    //     $filterString = implode(' AND ', $filterConditions);

    //     $query = Ad::search($keyword, function (\Meilisearch\Endpoints\Indexes $meilisearchIndex, $query, $options) use ($filterString) {
    //         if (!empty($filterString)) {
    //             $options['filter'] = $filterString;
    //         }
    //         return $meilisearchIndex->search($query, $options);
    //     });

    //     $ads = $query->paginate(20);
    //     $ads->load('images');
    //     $ads->getCollection()->transform(function ($ad) {
    //     return $ad->appendFirstImageUrl();
    //     });

    //     // ✅ Category detection
    //     $detectedCategoryId = null;
    //     if (!$categoryId && $ads->total() > 0) {
    //         $categoryCounts = collect($ads->items())->groupBy('category_id')->map->count();
    //         $detectedCategoryId = $categoryCounts->sortDesc()->keys()->first();
    //     }

    //     return response()->json([
    //         'total' => $ads->total(),
    //         'ads' => $ads->items(),
    //         'detected_category_id' => $detectedCategoryId,
    //         'last_page' => $ads->lastPage(),
    //     ]);
    // }

    public function searchResults(Request $request)
    {
        $keyword = trim($request->get('keyword'));
        $city = $request->get('city');
        $categoryId = $request->get('category_id');
        $dynamicFilters = $request->get('filters', []);

        $filterConditions = [];

        if ($city) {
            $filterConditions[] = 'city = "' . $city . '"';
        }

        if ($categoryId) {
            $filterConditions[] = 'category_id = ' . (int) $categoryId;
        }

        foreach ($dynamicFilters as $fieldId => $value) {
            $filterConditions[] = 'dynamic_values = "' . addslashes($value) . '"';
        }

        $filterString = implode(' AND ', $filterConditions);

        $query = Ad::search($keyword, function (\Meilisearch\Endpoints\Indexes $meilisearchIndex, $query, $options) use ($filterString) {
            if (!empty($filterString)) {
                $options['filter'] = $filterString;
            }
            return $meilisearchIndex->search($query, $options);
        });

        $ads = $query->paginate(20);
        $ads->load(['images','user.companyProfile']); // or ['images', 'user'] if getting user_type from user->role

        $ads->getCollection()->transform(function ($ad) {
            return [
                ...$ad->appendFirstImageUrl()->toArray(),
                'user_type' => $ad->user_type,
                'company_verified' => $ad->user?->companyProfile?->registration_document_status === 'verified',

            ];
        });

        $detectedCategoryId = null;
        if (!$categoryId && $ads->total() > 0) {
            $categoryCounts = collect($ads->items())->groupBy('category_id')->map->count();
            $detectedCategoryId = $categoryCounts->sortDesc()->keys()->first();
        }

        return response()->json([
            'total' => $ads->total(),
            'ads' => $ads->items(),
            'detected_category_id' => $detectedCategoryId,
            'last_page' => $ads->lastPage(),
        ]);
    }




    public function categoryFilters($id)
    {
        $category = Category::with(['dynamicFields.options'])->findOrFail($id);

        $filters = $category->dynamicFields->map(function ($field) {
            return [
                'id' => $field->id,
                'field_name' => $field->field_name,
                'field_type' => $field->field_type,
                'is_required' => $field->is_required,
                'options' => $field->options->pluck('value'),  // Assuming you have options for select fields
            ];
        });

        return response()->json($filters);
    }








    /**
     * (Run this ONCE) Make Meilisearch fields like city, subcategory filterable
     */
    public function setupMeilisearchFilters()
    {
        $client = app(\Meilisearch\Client::class);
        $index = $client->index('ads');

        $index->updateFilterableAttributes([
            'city',
            'category_id',      
            'subcategory_id',
            'dynamic_values'
        ]);

        return response()->json(['status' => 'Filterable fields updated.']);
    }
}
