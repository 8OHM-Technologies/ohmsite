<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\HomeSetting;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function test()
    {
        try {
            return Inertia::render('Test');
        } catch (\Throwable $e) {
            return Inertia::render('Test');
        }
    }

    public function privacyPolicy()
    {
        return view('privacy-policy');
    }

    public function refundCancellation()
    {
        return view('refund-and-cancellation');
    }

    public function terms()
    {
        return view('terms');
    }

    public function fairUsage()
    {
        return view('fair-usage');
    }

    public function index()
    {
        try {
            $user = auth()->user();

            $heroSlider = HomeSetting::where('key', 'hero_slider')->first();

            $heroProducts = [];
            if ($heroSlider && isset($heroSlider->value)) {
                foreach ($heroSlider->value as $slide) {
                    $isFavorited = false;
                    if ($user && isset($slide['product_id'])) {
                        $isFavorited = $user->favorites()->where('product_id', $slide['product_id'])->exists();
                    }

                    $heroProducts[] = [
                        'id' => $slide['product_id'] ?? null,
                        'title' => $slide['title'] ?? '',
                        'price' => $slide['price'] ?? '',
                        'category' => $slide['category'] ?? '',
                        'image' => $slide['image'] ?? '',
                        'is_favorited' => $isFavorited,
                    ];
                }
            }

            $categories = Category::all();

            $popularProducts = Product::where('clicks', '>', 0)->orderBy('clicks', 'desc')->take(10)->get();

            $newArrivals = Product::where('created_at', '>=', Carbon::now()->subWeek())->orderBy('created_at', 'desc')->get();

            $aboutUs = HomeSetting::where('key', 'about_us')->first();

            $roadmap = HomeSetting::where('key', 'roadmap')->first();
            $sectionHeaders = HomeSetting::where('key', 'section_headers')->first();

            $products = Product::whereIn('slug', [
                'once-off-dataset',
                'developer-api',
                'pro-analytics',
                'managed-data-pipeline',
            ])->get();

            $datasets = Dataset::where('is_active', true)->select('id', 'name', 'slug', 'description')->get();

            return Inertia::render('Home', [
                'heroProducts' => $heroProducts,
                'categories' => $categories,
                'popularProducts' => $popularProducts,
                'newArrivals' => $newArrivals,
                'aboutUs' => $aboutUs ? $aboutUs->value : null,
                'roadmapItems' => $roadmap ? $roadmap->value : null,
                'sectionHeaders' => $sectionHeaders ? $sectionHeaders->value : null,
                'products' => $products,
                'datasets' => $datasets,
            ]);
        } catch (\Throwable $e) {
            return Inertia::render('Home', [
                'heroProducts' => [],
                'categories' => [],
                'popularProducts' => [],
                'newArrivals' => [],
                'aboutUs' => null,
                'roadmapItems' => null,
                'sectionHeaders' => null,
                'products' => [],
                'datasets' => [],
            ]);
        }
    }

    public function datasetSample(string $slug)
    {
        $dataset = Dataset::where('slug', $slug)->where('is_active', true)->firstOrFail();
        
        $data = $dataset->demo_data ?? [];

        $keysToRemove = [
            'date_modified',
            'detail_url',
            'detail_title',
            'employee',
            'preview_image_url',
            'details_scraped_at'
        ];

        if (is_array($data)) {
            if (array_is_list($data)) {
                $data = array_map(function ($item) use ($keysToRemove) {
                    if (is_array($item)) {
                        foreach ($keysToRemove as $key) {
                            unset($item[$key]);
                        }
                    }
                    return $item;
                }, $data);
            } else {
                foreach ($keysToRemove as $key) {
                    unset($data[$key]);
                }
            }
        }

        return response()->json($data);
    }
}
